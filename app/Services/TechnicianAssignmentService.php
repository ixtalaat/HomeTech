<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\RequestStatus;
use App\Exceptions\TechnicianAssignmentException;
use App\Models\MaintenanceRequest;
use App\Models\Technician;
use App\Models\User;
use App\Notifications\JobAssigned;
use App\Notifications\TechnicianAssignedToRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TechnicianAssignmentService
{
    public function __construct(
        private RequestStatusService $transitions,
        private SchedulingService $scheduling
    ) {}

    /**
     * Assign a technician to an approved maintenance request (BR-001) and book the slot.
     *
     * The slot defaults to the preferred date/time with the end derived from
     * the service estimated duration. A conflicting slot fails the whole
     * assignment — nothing is partially booked.
     *
     * @param  array{date?: ?string, start?: ?string, end?: ?string}  $slot
     *
     * @throws TechnicianAssignmentException
     */
    public function assign(MaintenanceRequest $request, Technician $technician, ?User $actor = null, array $slot = []): MaintenanceRequest
    {
        $this->guardAssignable($request, $technician);

        [$date, $start, $end] = $this->resolveSlot($request, $slot);

        if ($this->scheduling->hasConflict($technician->id, $date, $start, $end)) {
            throw new TechnicianAssignmentException(
                "Technician '{$technician->user->name}' already has an overlapping appointment on {$date}. Pick another technician or slot."
            );
        }

        return DB::transaction(function () use ($request, $technician, $actor, $date, $start, $end): MaintenanceRequest {
            $request->update(['technician_id' => $technician->id]);

            $this->transitions->transition(
                $request->refresh(),
                RequestStatus::TechnicianAssigned,
                $actor,
                "Assigned to {$technician->user->name}."
            );

            $this->scheduling->book($request->refresh(), $technician, $date, $start, $end, $actor);

            $request->user->notify(new TechnicianAssignedToRequest($request->refresh(), $technician->user->name));
            $technician->user->notify(new JobAssigned($request->refresh()));

            return $request->refresh();
        });
    }

    /**
     * Reassign a request that is already assigned to another technician.
     *
     * The existing appointment moves to the new technician after a conflict
     * check, so the calendar never holds a stale booking.
     *
     * @throws TechnicianAssignmentException
     */
    public function reassign(MaintenanceRequest $request, Technician $technician, ?User $actor = null): MaintenanceRequest
    {
        if ($request->status === RequestStatus::Scheduled) {
            $this->guardTechnicianEligible($request, $technician);

            $appointment = $request->appointment;

            if ($appointment !== null && ! $appointment->isCancelled()) {
                if ($this->scheduling->hasConflict($technician->id, $appointment->date->format('Y-m-d'), $appointment->start_time, $appointment->end_time)) {
                    throw new TechnicianAssignmentException(
                        "Technician '{$technician->user->name}' already has an overlapping appointment on {$appointment->date->format('Y-m-d')}."
                    );
                }
            }

            return DB::transaction(function () use ($request, $technician, $actor, $appointment): MaintenanceRequest {
                $request->update(['technician_id' => $technician->id]);

                if ($appointment !== null) {
                    $appointment->update(['technician_id' => $technician->id]);
                }

                $request->statusHistories()->create([
                    'from_status' => RequestStatus::Scheduled->value,
                    'status' => RequestStatus::Scheduled->value,
                    'changed_by' => $actor?->id,
                    'reason' => "Reassigned to {$technician->user->name}.",
                ]);

                $request->user->notify(new TechnicianAssignedToRequest($request->refresh(), $technician->user->name));
                $technician->user->notify(new JobAssigned($request->refresh()));

                return $request->refresh();
            });
        }

        return $this->assign($request, $technician, $actor);
    }

    /**
     * Unassign the technician, cancelling the appointment and returning the request to approved.
     *
     * @throws TechnicianAssignmentException
     */
    public function unassign(MaintenanceRequest $request, ?User $actor = null): MaintenanceRequest
    {
        if (! in_array($request->status, [RequestStatus::TechnicianAssigned, RequestStatus::Scheduled, RequestStatus::TechnicianOnWay], true)) {
            throw new TechnicianAssignmentException(__('Only an assigned request can be unassigned.'));
        }

        return DB::transaction(function () use ($request, $actor): MaintenanceRequest {
            $request->update(['technician_id' => null]);

            $appointment = $request->appointment;

            if ($appointment !== null && ! $appointment->isCancelled()) {
                $appointment->update(['status' => AppointmentStatus::Cancelled]);
            }

            $this->transitions->transition(
                $request->refresh(),
                RequestStatus::Approved,
                $actor,
                'Technician unassigned; appointment cancelled.'
            );

            return $request->refresh();
        });
    }

    /**
     * Get technicians eligible for the given request (active + skilled for its category).
     *
     * @return Collection<int, Technician>
     */
    public function eligibleFor(MaintenanceRequest $request): Collection
    {
        $categoryId = $request->service->service_category_id;

        return Technician::active()
            ->whereHas('user', fn ($query): Builder => $query->where('is_active', true))
            ->whereHas('categories', fn ($query): Builder => $query->where('service_categories.id', $categoryId))
            ->with('user')
            ->withCount('assignedRequests')
            ->orderBy('assigned_requests_count')
            ->get();
    }

    /**
     * Resolve the booking slot, defaulting to the preferred appointment.
     *
     * @param  array{date?: ?string, start?: ?string, end?: ?string}  $slot
     * @return array{string, string, string}
     */
    private function resolveSlot(MaintenanceRequest $request, array $slot): array
    {
        $date = $slot['date'] ?? $request->preferred_date->format('Y-m-d');
        $start = $slot['start'] ?? Carbon::parse($request->preferred_time)->format('H:i');

        $end = $slot['end'] ?? Carbon::parse($start)
            ->addMinutes($request->service->estimated_duration_minutes)
            ->format('H:i');

        return [$date, $start, $end];
    }

    /**
     * Guard that the request is in an assignable state and the technician is eligible.
     *
     * @throws TechnicianAssignmentException
     */
    private function guardAssignable(MaintenanceRequest $request, Technician $technician): void
    {
        if ($request->status !== RequestStatus::Approved) {
            throw new TechnicianAssignmentException(
                "Cannot assign technician to request #{$request->id} with status '{$request->status->value}'. Only approved requests can be assigned."
            );
        }

        $this->guardTechnicianEligible($request, $technician);
    }

    /**
     * Guard that the technician is active and supports the request category (BR-001).
     *
     * @throws TechnicianAssignmentException
     */
    private function guardTechnicianEligible(MaintenanceRequest $request, Technician $technician): void
    {
        if (! $technician->is_active || $technician->user === null || ! $technician->user->is_active) {
            throw new TechnicianAssignmentException(__('Cannot assign an inactive technician.'));
        }

        $category = $request->service->category;

        if ($category === null || ! $technician->supportsCategory($category->id)) {
            throw new TechnicianAssignmentException(
                "Technician '{$technician->user->name}' does not support the '{$request->service->name}' service category."
            );
        }
    }
}
