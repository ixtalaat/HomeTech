<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderPolicy
{
    /**
     * Determine whether the user can view any work orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Technician
            || in_array($user->role, [UserRole::Admin, UserRole::Manager], true);
    }

    /**
     * Determine whether the user can view the work order.
     */
    public function view(User $user, WorkOrder $workOrder): bool
    {
        return $this->isOwner($user, $workOrder) || $this->isStaff($user);
    }

    /**
     * Determine whether the user can update the work order.
     *
     * Completed orders are locked here too (BR-007); the service re-checks.
     */
    public function update(User $user, WorkOrder $workOrder): bool
    {
        if ($workOrder->isCompleted()) {
            return false;
        }

        return $this->isOwner($user, $workOrder);
    }

    /**
     * Determine whether the user can correct the work order.
     */
    public function correct(User $user, WorkOrder $workOrder): bool
    {
        return $this->isStaff($user);
    }

    /**
     * Determine whether the user owns the work order's assignment.
     */
    private function isOwner(User $user, WorkOrder $workOrder): bool
    {
        return $user->role === UserRole::Technician
            && $workOrder->technician !== null
            && $workOrder->technician->user_id === $user->id;
    }

    /**
     * Determine whether the user is admin or manager staff.
     */
    private function isStaff(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager], true);
    }
}
