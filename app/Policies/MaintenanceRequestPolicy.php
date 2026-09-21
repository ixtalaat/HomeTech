<?php

namespace App\Policies;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\MaintenanceRequest;
use App\Models\User;

class MaintenanceRequestPolicy
{
    /**
     * Determine whether the user can view any requests.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the request.
     */
    public function view(User $user, MaintenanceRequest $request): bool
    {
        return $request->isOwnedBy($user) || $this->isStaff($user);
    }

    /**
     * Determine whether the user can create requests.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can review the request.
     */
    public function review(User $user, MaintenanceRequest $request): bool
    {
        return $this->isStaff($user) && $request->isReviewable();
    }

    /**
     * Determine whether the user can update the appointment of the request.
     */
    public function updateAppointment(User $user, MaintenanceRequest $request): bool
    {
        return $this->isStaff($user) && $request->isReviewable();
    }

    /**
     * Determine whether the user can manage the booked appointment
     * (assign/unassign/book/reschedule/cancel after review).
     */
    public function manageAppointment(User $user, MaintenanceRequest $request): bool
    {
        return $this->isStaff($user)
            && in_array($request->status, [RequestStatus::Approved, RequestStatus::TechnicianAssigned, RequestStatus::Scheduled, RequestStatus::TechnicianOnWay], true);
    }

    /**
     * Determine whether the user is admin or manager staff.
     */
    private function isStaff(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager], true);
    }
}
