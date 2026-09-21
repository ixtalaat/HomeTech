<?php

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Exceptions\BillingException;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Services\CancellationService;

it('cancels requests without appointments for free', function () {
    $service = app(CancellationService::class);
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $request = MaintenanceRequest::factory()->create(['user_id' => $customer->id]);

    $cancellation = $service->cancel($request, $customer, 'No longer needed.');

    expect((float) $cancellation->fee)->toBe(0.0)
        ->and($request->refresh()->status)->toBe(RequestStatus::Cancelled);
});

it('cancels far-future appointments for free and near ones with a fee (BR-008)', function () {
    $service = app(CancellationService::class);

    $far = scheduledRequest();
    $far->appointment->update(['date' => now()->addDays(5)->format('Y-m-d')]);
    $free = $service->cancel($far->refresh(), $far->user, 'Plans changed.');
    expect((float) $free->fee)->toBe(0.0);

    $near = scheduledRequest();
    $near->appointment->update([
        'date' => now()->format('Y-m-d'),
        'start_time' => now()->addHours(2)->format('H:i'),
        'end_time' => now()->addHours(3)->format('H:i'),
    ]);
    $base = (float) $near->service->base_price;
    $late = $service->cancel($near->refresh(), $near->user, 'Emergency came up.');
    expect((float) $late->fee)->toBe(round($base * 10 / 100, 2));
});

it('blocks cancellation once the job started and prevents duplicates', function () {
    $service = app(CancellationService::class);
    $workOrder = inProgressWorkOrder();

    expect(fn () => $service->cancel($workOrder->request->refresh(), $workOrder->request->user, 'Too late.'))
        ->toThrow(BillingException::class);

    $request = scheduledRequest();
    $service->cancel($request->refresh(), $request->user, 'First.');
    expect(fn () => $service->cancel($request->refresh(), $request->user, 'Second.'))
        ->toThrow(BillingException::class);
});

it('lets customers cancel their own cancellable requests via HTTP', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $other = User::factory()->create(['role' => UserRole::Customer]);
    $request = MaintenanceRequest::factory()->approved()->create(['user_id' => $customer->id]);

    $this->actingAs($other)->post(route('requests.cancel', $request), ['reason' => 'Mine now.'])
        ->assertNotFound();

    $this->actingAs($customer)->post(route('requests.cancel', $request), ['reason' => 'Found another provider.'])
        ->assertRedirect();

    expect($request->refresh()->status)->toBe(RequestStatus::Cancelled);
});
