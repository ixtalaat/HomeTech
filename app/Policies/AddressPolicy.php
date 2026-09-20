<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Address;
use App\Models\User;

class AddressPolicy
{
    /**
     * Determine whether the user can view any addresses.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the address.
     */
    public function view(User $user, Address $address): bool
    {
        return $address->isOwnedBy($user) || $this->isStaff($user);
    }

    /**
     * Determine whether the user can create addresses.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the address.
     */
    public function update(User $user, Address $address): bool
    {
        return $address->isOwnedBy($user);
    }

    /**
     * Determine whether the user can delete the address.
     */
    public function delete(User $user, Address $address): bool
    {
        return $address->isOwnedBy($user);
    }

    /**
     * Determine whether the user can mark the address as default.
     */
    public function setDefault(User $user, Address $address): bool
    {
        return $address->isOwnedBy($user);
    }

    /**
     * Determine whether the user is admin or manager staff.
     */
    private function isStaff(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager], true);
    }
}
