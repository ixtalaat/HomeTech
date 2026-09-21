<?php

use App\Enums\RequestStatus;
use App\Enums\WorkOrderStatus;
use App\Models\MaintenanceRequest;
use App\Models\Technician;
use App\Notifications\TechnicianOnWay;
use App\Services\SchedulingService;
use App\Services\WorkOrderService;
use Illuminate\Support\Facades\Notification;

it('marks the technician as on the way and notifies the customer', function () {
    Notification::fake();

    $request = scheduledRequest();
    $techUser = $request->technician->user;

    $this->actingAs($techUser)->post(route('technician.jobs.on-way', $request))->assertRedirect();

    expect($request->refresh()->status)->toBe(RequestStatus::TechnicianOnWay);

    Notification::assertSentTo($request->user, TechnicianOnWay::class);
});

it('refuses on-the-way for foreign technicians and non-scheduled jobs', function () {
    $request = scheduledRequest();
    $otherTech = Technician::factory()->create();

    $this->actingAs($otherTech->user)->post(route('technician.jobs.on-way', $request))->assertNotFound();

    $approved = MaintenanceRequest::factory()->approved()->create();
    $approved->update(['technician_id' => $request->technician_id]);
    $this->actingAs($request->technician->user)->post(route('technician.jobs.on-way', $approved))
        ->assertSessionHas('error');

    expect($request->refresh()->status)->toBe(RequestStatus::Scheduled);
});

it('starts the visit from on the way and still allows reschedule', function () {
    $request = scheduledRequest();
    $techUser = $request->technician->user;

    app(SchedulingService::class)->markOnWay($request->refresh(), $techUser);

    $workOrder = app(WorkOrderService::class)->startVisit($request->refresh(), $techUser);

    expect($workOrder->status)->toBe(WorkOrderStatus::InProgress)
        ->and($request->refresh()->status)->toBe(RequestStatus::InProgress);
});
