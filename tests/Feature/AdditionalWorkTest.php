<?php

use App\Enums\AdditionalWorkStatus;
use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Exceptions\AdditionalWorkException;
use App\Models\AdditionalWork;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\AdditionalWorkRequiresApproval;
use App\Services\AdditionalWorkService;

it('requests additional work and notifies the customer', function () {
    $service = app(AdditionalWorkService::class);
    $workOrder = inProgressWorkOrder();
    $techUser = $workOrder->technician->user;
    $customer = $workOrder->request->user;

    $item = $service->request($workOrder, $techUser, 'Replace damaged main pipe.', 500.00);

    expect($item->status)->toBe(AdditionalWorkStatus::PendingApproval)
        ->and($workOrder->request->refresh()->status)->toBe(RequestStatus::WaitingCustomerApproval)
        ->and($customer->notifications()->where('type', AdditionalWorkRequiresApproval::class)->count())->toBe(1);
});

it('approves additional work with timestamp and returns the job to progress', function () {
    $service = app(AdditionalWorkService::class);
    $workOrder = inProgressWorkOrder();
    $customer = $workOrder->request->user;

    $item = $service->request($workOrder, $workOrder->technician->user, 'Replace damaged main pipe.', 500.00);

    $this->actingAs($customer)->patch(route('additional-work.approve', $item))->assertRedirect();

    $item = $item->refresh();

    expect($item->status)->toBe(AdditionalWorkStatus::Approved)
        ->and($item->decided_by)->toBe($customer->id)
        ->and($item->decided_at)->not->toBeNull()
        ->and($item->workOrder->request->refresh()->status)->toBe(RequestStatus::InProgress)
        ->and($service->billableFor($workOrder)->pluck('id')->all())->toContain($item->id);
});

it('rejects additional work and excludes it from billing (BR-005)', function () {
    $service = app(AdditionalWorkService::class);
    $workOrder = inProgressWorkOrder();
    $customer = $workOrder->request->user;

    $item = $service->request($workOrder, $workOrder->technician->user, 'Replace damaged main pipe.', 500.00);

    $this->actingAs($customer)->patch(route('additional-work.reject', $item))->assertRedirect();

    $item = $item->refresh();

    expect($item->status)->toBe(AdditionalWorkStatus::Rejected)
        ->and($item->workOrder->request->refresh()->status)->toBe(RequestStatus::InProgress)
        ->and($service->billableFor($workOrder))->toBeEmpty();
});

it('blocks completion while additional work is pending', function () {
    $workOrder = inProgressWorkOrder();
    app(AdditionalWorkService::class)->request($workOrder, $workOrder->technician->user, 'Extra pipe.', 100.00);

    expect($workOrder->refresh()->unmetCompletionRequirements())->not->toBeEmpty();
});

it('forbids self-approval, foreign decisions, and invalid states', function () {
    $service = app(AdditionalWorkService::class);
    $workOrder = inProgressWorkOrder();
    $techUser = $workOrder->technician->user;
    $stranger = User::factory()->create(['role' => UserRole::Customer]);

    $item = $service->request($workOrder, $techUser, 'Extra pipe.', 100.00);

    // Technician cannot approve own request.
    expect(fn () => $service->decide($item->refresh(), $techUser, true))
        ->toThrow(AdditionalWorkException::class);

    // Non-owner cannot decide.
    expect(fn () => $service->decide($item->refresh(), $stranger, true))
        ->toThrow(AdditionalWorkException::class);

    $this->actingAs($stranger)->patch(route('additional-work.approve', $item))->assertForbidden();

    // Deciding twice is refused.
    $service->decide($item->refresh(), $workOrder->request->user, true);
    expect(fn () => $service->decide($item->refresh(), $workOrder->request->user, false))
        ->toThrow(AdditionalWorkException::class);

    // Cannot request on a completed order (BR-007).
    $completed = WorkOrder::factory()->completed()->create();
    expect(fn () => $service->request($completed, $completed->technician->user, 'Late extra.', 50.00))
        ->toThrow(AdditionalWorkException::class);
});

it('marks approved work as performed', function () {
    $service = app(AdditionalWorkService::class);
    $workOrder = inProgressWorkOrder();
    $techUser = $workOrder->technician->user;

    $item = $service->request($workOrder, $techUser, 'Extra pipe.', 100.00);
    $service->decide($item->refresh(), $workOrder->request->user, true);

    // Rejected items cannot be marked performed.
    $rejected = AdditionalWork::factory()->rejected()->create(['work_order_id' => $workOrder->id]);
    expect(fn () => $service->markCompleted($rejected, $techUser))
        ->toThrow(AdditionalWorkException::class);

    $done = $service->markCompleted($item->refresh(), $techUser);

    expect($done->status)->toBe(AdditionalWorkStatus::Completed)
        ->and($done->completed_at)->not->toBeNull();

    $this->actingAs($techUser)->patch(route('technician.jobs.additional-work.complete', $done))->assertSessionHas('error');
});
