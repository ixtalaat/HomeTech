<?php

use App\Services\SchedulingService;
use App\Services\TechnicianAssignmentService;
use App\Services\WorkOrderService;
use Illuminate\Support\Facades\Log;

it('messages customers on WhatsApp for assignment, on-way and completion', function () {
    Log::spy();

    $request = scheduledRequest();
    $customer = $request->user;
    $customer->update(['phone' => '+201000000001']);
    $techUser = $request->technician->user;

    app(SchedulingService::class)->markOnWay($request->refresh(), $techUser);

    Log::shouldHaveReceived('info')->with(Mockery::on(
        fn (string $message): bool => str_contains($message, '+201000000001') && str_contains($message, 'on the way')
    ));

    $workOrder = app(WorkOrderService::class)->startVisit($request->refresh(), $techUser);
    app(WorkOrderService::class)->recordDiagnosis($workOrder, $techUser, 'Worn cartridge.');
    app(WorkOrderService::class)->recordNotes($workOrder, $techUser, 'Replaced and tested.');
    app(WorkOrderService::class)->complete($workOrder->refresh(), $techUser);

    Log::shouldHaveReceived('info')->with(Mockery::on(
        fn (string $message): bool => str_contains($message, '+201000000001') && str_contains($message, 'completed')
    ));
});

it('messages the customer when a technician is assigned', function () {
    Log::spy();

    $service = hourlyService();
    $branch = staffedBranch('Riyadh');
    $technician = workingTechnician($service, $branch);
    $request = mondayRequest($service, 'Riyadh');
    $request->user->update(['phone' => '+201000000002']);

    app(TechnicianAssignmentService::class)->autoAssign($request);

    Log::shouldHaveReceived('info')->with(Mockery::on(
        fn (string $message): bool => str_contains($message, '+201000000002') && str_contains($message, 'assigned')
    ));
});

it('shows live status banners on the customer request page', function () {
    $request = scheduledRequest();
    $customer = $request->user;
    $techUser = $request->technician->user;

    app(SchedulingService::class)->markOnWay($request->refresh(), $techUser);

    $this->actingAs($customer)->get(route('requests.show', $request))
        ->assertOk()
        ->assertSee($techUser->name, false)
        ->assertSee('is on the way to you', false);

    $workOrder = app(WorkOrderService::class)->startVisit($request->refresh(), $techUser);

    $this->actingAs($customer)->get(route('requests.show', $workOrder->request))
        ->assertOk()
        ->assertSee('Work is in progress on your request.', false);
});

it('highlights visits scheduled for today', function () {
    $request = scheduledRequest();
    $request->appointment->update(['date' => now()->format('Y-m-d')]);

    $this->actingAs($request->user)->get(route('requests.show', $request))
        ->assertOk()
        ->assertSee('Visit today at', false);
});
