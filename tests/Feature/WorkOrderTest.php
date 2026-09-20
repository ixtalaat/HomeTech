<?php

use App\Enums\AppointmentStatus;
use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Enums\WorkOrderStatus;
use App\Exceptions\CompletedWorkOrderException;
use App\Exceptions\WorkOrderException;
use App\Models\AuditLog;
use App\Models\MaintenanceRequest;
use App\Models\Technician;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\WorkOrderService;

it('starts the visit for the assigned technician', function () {
    $request = scheduledRequest();
    $technicianUser = $request->technician->user;

    $workOrder = app(WorkOrderService::class)->startVisit($request->refresh(), $technicianUser);

    expect($workOrder->status)->toBe(WorkOrderStatus::InProgress)
        ->and($request->refresh()->status)->toBe(RequestStatus::InProgress)
        ->and($workOrder->technician_id)->toBe($request->technician_id);
});

it('refuses to start visits for invalid states and actors', function () {
    $service = app(WorkOrderService::class);
    $request = scheduledRequest();
    $otherTech = Technician::factory()->create();

    // Wrong technician.
    expect(fn () => $service->startVisit($request->refresh(), $otherTech->user))
        ->toThrow(WorkOrderException::class);

    // Non-scheduled request.
    $pending = MaintenanceRequest::factory()->create();
    expect(fn () => $service->startVisit($pending, $request->technician->user))
        ->toThrow(WorkOrderException::class);

    // Cancelled appointment with the request still marked scheduled.
    $request->appointment->update(['status' => AppointmentStatus::Cancelled]);
    expect(fn () => $service->startVisit($request->refresh(), $request->technician->user))
        ->toThrow(WorkOrderException::class);
});

it('blocks completion until diagnosis and notes are recorded', function () {
    $service = app(WorkOrderService::class);
    $workOrder = inProgressWorkOrder();

    expect(fn () => $service->complete($workOrder, $workOrder->technician->user))
        ->toThrow(WorkOrderException::class);

    $service->recordDiagnosis($workOrder->refresh(), $workOrder->technician->user, 'Faulty capacitor.');
    $service->recordNotes($workOrder->refresh(), $workOrder->technician->user, 'Replaced capacitor, tested OK.');

    $completed = $service->complete($workOrder->refresh(), $workOrder->technician->user);

    expect($completed->status)->toBe(WorkOrderStatus::Completed)
        ->and($completed->request->refresh()->status)->toBe(RequestStatus::Completed)
        ->and($completed->appointment->refresh()->status)->toBe(AppointmentStatus::Completed);
});

it('calculates labor totals correctly', function () {
    $service = app(WorkOrderService::class);
    $workOrder = inProgressWorkOrder();
    $techUser = $workOrder->technician->user;

    $service->addLaborItem($workOrder, $techUser, 'AC Diagnosis', 100.00);
    $service->addLaborItem($workOrder, $techUser, 'Capacitor Repair', 150.50);

    expect($workOrder->refresh()->laborTotal())->toBe(250.50);
});

it('validates work photo uploads', function () {
    $techUser = inProgressWorkOrder()->technician->user;
    $workOrder = WorkOrder::first();

    $this->actingAs($techUser)->post(route('technician.jobs.photos', $workOrder), [
        'slot' => 'before',
        'photos' => [fakePngPhoto('before.png')],
    ])->assertRedirect();

    expect($workOrder->refresh()->before_photos)->toHaveCount(1);

    $this->actingAs($techUser)->post(route('technician.jobs.photos', $workOrder), [
        'slot' => 'sideways',
        'photos' => [fakePngPhoto()],
    ])->assertSessionHasErrors('slot');
});

it('locks completed work orders for technicians but allows logged staff corrections (BR-007)', function () {
    $service = app(WorkOrderService::class);
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $workOrder = WorkOrder::factory()->completed()->create();
    $techUser = $workOrder->technician->user;

    expect(fn () => $service->recordDiagnosis($workOrder, $techUser, 'Late edit'))
        ->toThrow(CompletedWorkOrderException::class);

    expect(fn () => $service->correct($workOrder, $techUser, ['diagnosis' => 'x'], 'nope'))
        ->toThrow(WorkOrderException::class);

    $service->correct($workOrder, $admin, ['diagnosis' => 'Corrected diagnosis.'], 'Typo in original report.');

    expect($workOrder->refresh()->diagnosis)->toBe('Corrected diagnosis.');

    $log = AuditLog::where('subject_type', $workOrder->getMorphClass())
        ->where('subject_id', $workOrder->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->actor_id)->toBe($admin->id)
        ->and($log->reason)->toBe('Typo in original report.');
});

it('gives technicians their own jobs portal', function () {
    $request = scheduledRequest();
    $techUser = $request->technician->user;
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($techUser)->get(route('technician.jobs.index'))->assertOk();
    $this->actingAs($customer)->get(route('technician.jobs.index'))->assertForbidden();

    $workOrder = inProgressWorkOrder();
    $this->actingAs($workOrder->technician->user)
        ->patch(route('technician.jobs.complete', $workOrder))
        ->assertSessionHas('error');

    $otherTech = Technician::factory()->create();
    $this->actingAs($otherTech->user)->get(route('technician.jobs.show', $workOrder))->assertNotFound();
});
