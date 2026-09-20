<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class CustomerPolicy
{
    /**
     * Determine whether the user can view any customers.
     */
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    /**
     * Determine whether the user can view the customer.
     *
     * Staff check only; the controller returns 404 for non-customer targets.
     */
    public function view(User $user, User $customer): bool
    {
        return $this->isStaff($user);
    }

    /**
     * Determine whether the user can update the customer.
     *
     * Staff check only; the controller and form request enforce customer-only targets.
     */
    public function update(User $user, User $customer): bool
    {
        return $this->isStaff($user);
    }

    /**
     * Determine whether the user can toggle the customer status.
     */
    public function toggleStatus(User $user, User $customer): bool
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
