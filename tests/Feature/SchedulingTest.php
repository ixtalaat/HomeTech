<?php

use App\Enums\AppointmentStatus;
use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Exceptions\CancelledAppointmentException;
use App\Exceptions\SchedulingConflictException;
use App\Exceptions\TechnicianAssignmentException;
use App\Models\Appointment;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Services\RequestStatusService;
use App\Services\SchedulingService;
use App\Services\TechnicianAssignmentService;

it('rejects overlapping appointments for the same technician (BR-002)', function () {
    $scheduling = app(SchedulingService::class);
    $request = scheduledRequest();
    $technician = $request->technician;
    $date = $request->appointment->date->format('Y-m-d');

    // PRD §12 example: 09:00–11:00 booked, 10:00–12:00 must be rejected.
    expect($scheduling->hasConflict($technician->id, $date, '10:00', '12:00'))->toBeTrue();

    $other = MaintenanceRequest::factory()->approved()->create();
    app(RequestStatusService::class)->transition($other, RequestStatus::TechnicianAssigned);
    $other->update(['technician_id' => $technician->id]);

    expect(fn () => $scheduling->book($other->refresh(), $technician, $date, '10:00', '12:00'))
        ->toThrow(SchedulingConflictException::class);
});

it('allows non-overlapping and boundary-touching slots', function () {
    $scheduling = app(SchedulingService::class);
    $request = scheduledRequest();
    $technician = $request->technician;
    $date = $request->appointment->date->format('Y-m-d');

    // Adjacent boundary: existing ends 11:00, new starts 11:00.
    expect($scheduling->hasConflict($technician->id, $date, '11:00', '13:00'))->toBeFalse();

    // Different technician, same slot.
    $otherTech = skilledTechnician($request->service);
    expect($scheduling->hasConflict($otherTech->id, $date, '10:00', '12:00'))->toBeFalse();

    // Different date, same slot.
    $otherDate = $request->appointment->date->copy()->addDays(30)->format('Y-m-d');
    expect($scheduling->hasConflict($technician->id, $otherDate, '10:00', '12:00'))->toBeFalse();
});

it('reuses slots freed by cancellation', function () {
    $scheduling = app(SchedulingService::class);
    $request = scheduledRequest();
    $technician = $request->technician;
    $date = $request->appointment->date->format('Y-m-d');

    $scheduling->cancel($request->appointment);

    expect($scheduling->hasConflict($technician->id, $date, '10:00', '12:00'))->toBeFalse();
});

it('reschedules with conflict re-check and history', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $request = scheduledRequest();
    $newDate = now()->addDays(9)->format('Y-m-d');

    $this->actingAs($admin)->patch(route('admin.requests.reschedule-appointment', $request), [
        'date' => $newDate,
        'start_time' => '14:00',
        'end_time' => '16:00',
    ])->assertRedirect();

    expect($request->appointment->refresh()->status)->toBe(AppointmentStatus::Rescheduled)
        ->and($request->appointment->date->format('Y-m-d'))->toBe($newDate);
});

it('rejects rescheduling into a conflicting slot', function () {
    $scheduling = app(SchedulingService::class);
    $request = scheduledRequest();
    $technician = $request->technician;
    $date = $request->appointment->date->format('Y-m-d');
    $originalStart = $request->appointment->start_time;

    // Occupy 14:00–16:00 with another request for the same technician.
    $other = MaintenanceRequest::factory()->approved()->create();
    $other->update(['technician_id' => $technician->id]);
    Appointment::factory()->create([
        'maintenance_request_id' => $other->id,
        'technician_id' => $technician->id,
        'date' => $date,
        'start_time' => '14:00',
        'end_time' => '16:00',
    ]);

    expect(fn () => $scheduling->reschedule($request->appointment, $date, '15:00', '17:00'))
        ->toThrow(SchedulingConflictException::class);

    expect($request->appointment->refresh()->start_time)->toBe($originalStart);
});

it('cancels the appointment and returns the request for rebooking', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $request = scheduledRequest();

    $this->actingAs($admin)->patch(route('admin.requests.cancel-appointment', $request))->assertRedirect();

    $request = $request->refresh();

    expect($request->status)->toBe(RequestStatus::TechnicianAssigned)
        ->and($request->appointment->status)->toBe(AppointmentStatus::Cancelled);
});

it('refuses to start a cancelled appointment', function () {
    $scheduling = app(SchedulingService::class);
    $request = scheduledRequest();
    $scheduling->cancel($request->appointment);

    expect(fn () => $scheduling->start($request->appointment->refresh()))
        ->toThrow(CancelledAppointmentException::class);
});

it('fails the whole assignment when the preferred slot conflicts', function () {
    $assignments = app(TechnicianAssignmentService::class);
    $request = MaintenanceRequest::factory()->approved()->create([
        'preferred_time' => '10:00',
    ]);
    $technician = skilledTechnician($request->service);
    $date = $request->preferred_date->format('Y-m-d');

    // Block the preferred slot with another appointment for the same technician.
    $other = MaintenanceRequest::factory()->approved()->create();
    $other->update(['technician_id' => $technician->id]);
    Appointment::factory()->create([
        'maintenance_request_id' => $other->id,
        'technician_id' => $technician->id,
        'date' => $date,
        'start_time' => '09:00',
        'end_time' => '12:00',
    ]);

    expect(fn () => $assignments->assign($request->refresh(), $technician))
        ->toThrow(TechnicianAssignmentException::class);

    $request = $request->refresh();

    expect($request->status)->toBe(RequestStatus::Approved)
        ->and($request->technician_id)->toBeNull()
        ->and($request->appointment)->toBeNull();
});

it('derives the booking end from the service duration', function () {
    $request = MaintenanceRequest::factory()->approved()->create([
        'preferred_time' => '10:00',
    ]);
    $request->service->update(['estimated_duration_minutes' => 90]);
    $technician = skilledTechnician($request->service);

    app(TechnicianAssignmentService::class)->assign($request->refresh(), $technician);

    expect($request->appointment->start_time)->toBe('10:00:00')
        ->and($request->appointment->end_time)->toBe('11:30:00');
});
