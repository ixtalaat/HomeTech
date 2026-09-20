<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Exceptions\TechnicianAssignmentException;
use App\Models\MaintenanceRequest;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TechnicianAssignmentService
{
    public function __construct(private RequestStatusService $transitions) {}

    /**
     * Assign a technician to an approved maintenance request (BR-001).
     *
     * @throws TechnicianAssignmentException
     */
    public function assign(MaintenanceRequest $request, Technician $technician, ?User $actor = null): MaintenanceRequest
    {
        $this->guardAssignable($request, $technician);

        return DB::transaction(function () use ($request, $technician, $actor): MaintenanceRequest {
            $request->update(['technician_id' => $technician->id]);

            $this->transitions->transition(
                $request->refresh(),
                RequestStatus::TechnicianAssigned,
                $actor,
                "Assigned to {$technician->user->name}."
            );

            return $request->refresh();
        });
    }

    /**
     * Reassign a request that is already assigned to another technician.
     *
     * @throws TechnicianAssignmentException
     */
    public function reassign(MaintenanceRequest $request, Technician $technician, ?User $actor = null): MaintenanceRequest
    {
        if ($request->status !== RequestStatus::TechnicianAssigned) {
            return $this->assign($request, $technician, $actor);
        }

        $this->guardTechnicianEligible($request, $technician);

        return DB::transaction(function () use ($request, $technician, $actor): MaintenanceRequest {
            $request->update(['technician_id' => $technician->id]);

            $request->statusHistories()->create([
                'from_status' => RequestStatus::TechnicianAssigned->value,
                'status' => RequestStatus::TechnicianAssigned->value,
                'changed_by' => $actor?->id,
                'reason' => "Reassigned to {$technician->user->name}.",
            ]);

            return $request->refresh();
        });
    }

    /**
     * Unassign the technician, returning the request to approved.
     *
     * @throws TechnicianAssignmentException
     */
    public function unassign(MaintenanceRequest $request, ?User $actor = null): MaintenanceRequest
    {
        if ($request->status !== RequestStatus::TechnicianAssigned) {
            throw new TechnicianAssignmentException('Only an assigned request can be unassigned.');
        }

        return DB::transaction(function () use ($request, $actor): MaintenanceRequest {
            $request->update(['technician_id' => null]);

            $this->transitions->transition(
                $request->refresh(),
                RequestStatus::Approved,
                $actor,
                'Technician unassigned.'
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
            throw new TechnicianAssignmentException('Cannot assign an inactive technician.');
        }

        $category = $request->service->category;

        if ($category === null || ! $technician->supportsCategory($category->id)) {
            throw new TechnicianAssignmentException(
                "Technician '{$technician->user->name}' does not support the '{$request->service->name}' service category."
            );
        }
    }
}
