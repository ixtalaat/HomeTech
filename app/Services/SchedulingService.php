<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\RequestStatus;
use App\Exceptions\CancelledAppointmentException;
use App\Exceptions\SchedulingConflictException;
use App\Models\Appointment;
use App\Models\MaintenanceRequest;
use App\Models\Technician;
use App\Models\User;
use App\Notifications\AppointmentChanged;
use App\Notifications\JobCancelled;
use App\Notifications\JobRescheduled;
use App\Notifications\TechnicianOnWay;
use Illuminate\Support\Facades\DB;

class SchedulingService
{
    public function __construct(private RequestStatusService $transitions) {}

    /**
     * Determine whether the technician has a conflicting appointment (BR-002).
     *
     * Cancelled appointments free the calendar; boundary-touching windows
     * (end == start) do not conflict.
     */
    public function hasConflict(int $technicianId, string $date, string $start, string $end, ?int $ignoreId = null): bool
    {
        $query = Appointment::forTechnicianOn($technicianId, $date)
            ->blocking()
            ->overlapping($start, $end);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    /**
     * Book an appointment for an assigned request.
     *
     * @throws SchedulingConflictException
     */
    public function book(MaintenanceRequest $request, Technician $technician, string $date, string $start, string $end, ?User $actor = null): Appointment
    {
        if ($request->status !== RequestStatus::TechnicianAssigned) {
            throw new SchedulingConflictException("Cannot book an appointment for request #{$request->id} with status '{$request->status->value}'.");
        }

        $this->guardFutureSlot($date, $start, $end);
        $this->guardNoConflict($technician, $date, $start, $end);

        return DB::transaction(function () use ($request, $technician, $date, $start, $end, $actor): Appointment {
            $appointment = Appointment::create([
                'maintenance_request_id' => $request->id,
                'technician_id' => $technician->id,
                'date' => $date,
                'start_time' => $start,
                'end_time' => $end,
                'status' => AppointmentStatus::Scheduled,
            ]);

            $this->transitions->transition(
                $request->refresh(),
                RequestStatus::Scheduled,
                $actor,
                "Appointment booked for {$date} {$start}–{$end}."
            );

            return $appointment;
        });
    }

    /**
     * Reschedule an appointment, re-running the conflict check (BR-002).
     *
     * @throws SchedulingConflictException
     */
    public function reschedule(Appointment $appointment, string $date, string $start, string $end, ?User $actor = null): Appointment
    {
        if ($appointment->isCancelled()) {
            throw new SchedulingConflictException(__('Cannot reschedule a cancelled appointment. Book a new one instead.'));
        }

        $this->guardFutureSlot($date, $start, $end);
        $this->guardNoConflict($appointment->technician, $date, $start, $end, $appointment->id);

        return DB::transaction(function () use ($appointment, $date, $start, $end, $actor): Appointment {
            $appointment->update([
                'date' => $date,
                'start_time' => $start,
                'end_time' => $end,
                'status' => AppointmentStatus::Rescheduled,
            ]);

            $appointment->request->statusHistories()->create([
                'from_status' => RequestStatus::Scheduled->value,
                'status' => RequestStatus::Scheduled->value,
                'changed_by' => $actor?->id,
                'reason' => "Appointment rescheduled to {$date} {$start}–{$end}.",
            ]);

            $summary = "{$date} {$start}–{$end}";
            $appointment->request->user->notify(new AppointmentChanged($appointment->request, $summary));
            $appointment->technician->user->notify(new JobRescheduled($appointment->request, $summary));

            return $appointment->refresh();
        });
    }

    /**
     * Cancel an appointment, returning the request to technician_assigned for rebooking.
     */
    public function cancel(Appointment $appointment, ?User $actor = null, ?string $reason = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $actor, $reason): Appointment {
            $appointment->update(['status' => AppointmentStatus::Cancelled]);

            $this->transitions->transition(
                $appointment->request->refresh(),
                RequestStatus::TechnicianAssigned,
                $actor,
                $reason ?? 'Appointment cancelled; awaiting a new slot.'
            );

            $appointment->technician->user->notify(
                new JobCancelled($appointment->request, $reason ?? 'The appointment was cancelled.')
            );

            return $appointment->refresh();
        });
    }

    /**
     * Mark the technician as on the way, notifying the customer.
     *
     * @throws SchedulingConflictException
     */
    public function markOnWay(MaintenanceRequest $request, User $technicianUser): MaintenanceRequest
    {
        if ($request->status !== RequestStatus::Scheduled) {
            throw new SchedulingConflictException(__('Only a scheduled job can be marked as on the way.'));
        }

        if ($request->technician === null || $request->technician->user_id !== $technicianUser->id) {
            throw new SchedulingConflictException(__('Only the assigned technician can mark this job as on the way.'));
        }

        if ($request->appointment === null || $request->appointment->isCancelled()) {
            throw new SchedulingConflictException(__('There is no active appointment for this job.'));
        }

        return DB::transaction(function () use ($request, $technicianUser): MaintenanceRequest {
            $this->transitions->transition(
                $request->refresh(),
                RequestStatus::TechnicianOnWay,
                $technicianUser,
                "Technician {$technicianUser->name} is on the way."
            );

            $request->user->notify(new TechnicianOnWay($request->refresh()));

            return $request->refresh();
        });
    }

    /**
     * Start the visit for an appointment. Refuses cancelled appointments (PRD §13).
     *
     * Epic 7 owns the visit lifecycle; this guard is pinned here.
     *
     * @throws CancelledAppointmentException
     */
    public function start(Appointment $appointment): Appointment
    {
        if ($appointment->isCancelled()) {
            throw new CancelledAppointmentException(__('Cannot start a cancelled appointment.'));
        }

        return $appointment;
    }

    /**
     * Guard that the slot is a valid future window.
     *
     * @throws SchedulingConflictException
     */
    private function guardFutureSlot(string $date, string $start, string $end): void
    {
        if ($end <= $start) {
            throw new SchedulingConflictException(__('The end time must be after the start time.'));
        }

        if ($date <= now()->format('Y-m-d')) {
            throw new SchedulingConflictException(__('Appointments must be booked for a future date.'));
        }
    }

    /**
     * Guard that the technician is free for the slot (BR-002).
     *
     * @throws SchedulingConflictException
     */
    private function guardNoConflict(Technician $technician, string $date, string $start, string $end, ?int $ignoreId = null): void
    {
        if ($this->hasConflict($technician->id, $date, $start, $end, $ignoreId)) {
            throw new SchedulingConflictException(
                "Technician '{$technician->user->name}' already has an overlapping appointment on {$date}."
            );
        }
    }
}
