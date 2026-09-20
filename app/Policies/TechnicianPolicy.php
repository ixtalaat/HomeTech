<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Technician;
use App\Models\User;

class TechnicianPolicy
{
    /**
     * Determine whether the user can view any technicians.
     */
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    /**
     * Determine whether the user can view the technician.
     */
    public function view(User $user, Technician $technician): bool
    {
        return $this->isStaff($user);
    }

    /**
     * Determine whether the user can create technicians.
     */
    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    /**
     * Determine whether the user can update the technician.
     */
    public function update(User $user, Technician $technician): bool
    {
        return $this->isStaff($user);
    }

    /**
     * Determine whether the user can toggle the technician status.
     */
    public function toggleStatus(User $user, Technician $technician): bool
    {
        return $this->isStaff($user);
    }

    /**
     * Determine whether the user is admin or manager staff.
     */
    private function isStaff(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager], true);
    }
}
