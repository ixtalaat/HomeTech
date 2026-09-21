<?php

use App\Enums\DiscountType;
use App\Enums\UserRole;
use App\Exceptions\BillingException;
use App\Models\DiscountApproval;
use App\Models\User;
use App\Notifications\DiscountApprovalRequested;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Notification;

it('queues over-threshold admin discounts for manager approval', function () {
    Notification::fake();

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $workOrder = completedWorkOrderWithCharges();
    $invoice = app(InvoiceService::class)->generate($workOrder, $admin);

    $this->actingAs($admin)->patch(route('admin.invoices.discount', $invoice), [
        'discount_type' => 'percent',
        'discount_value' => 25,
    ])->assertSessionHasNoErrors();

    expect((float) $invoice->refresh()->discount_amount)->toBe(0.0);

    $approval = DiscountApproval::first();
    expect($approval)->not->toBeNull()
        ->and($approval->status->value)->toBe('pending')
        ->and($approval->requested_by)->toBe($admin->id);

    Notification::assertSentTo($manager, DiscountApprovalRequested::class);
});

it('lets managers approve and reject queued discounts', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $service = app(InvoiceService::class);

    $workOrder = completedWorkOrderWithCharges();
    $invoice = $service->generate($workOrder, $admin);
    $approval = $service->requestDiscountApproval($invoice, $admin, DiscountType::Percent, 25.00);

    // Admins cannot decide.
    expect(fn () => $service->approveDiscountRequest($approval->refresh(), $admin))
        ->toThrow(BillingException::class);

    $service->approveDiscountRequest($approval->refresh(), $manager);

    expect($approval->refresh()->status->value)->toBe('approved')
        ->and((float) $invoice->refresh()->discount_amount)->toBeGreaterThan(0);

    // Double decisions refused.
    expect(fn () => $service->approveDiscountRequest($approval->refresh(), $manager))
        ->toThrow(BillingException::class);

    $second = $service->requestDiscountApproval($invoice->refresh(), $admin, DiscountType::Fixed, 600.00);

    $this->actingAs($manager)->patch(route('admin.discount-approvals.reject', $second))->assertRedirect();

    expect($second->refresh()->status->value)->toBe('rejected');
    expect(DiscountApproval::pending()->count())->toBe(0);
});
