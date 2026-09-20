<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\InventoryItem;
use App\Models\User;

class InventoryPolicy
{
    /**
     * Determine whether the user can view any inventory items.
     */
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    /**
     * Determine whether the user can view the inventory item.
     */
    public function view(User $user, InventoryItem $item): bool
    {
        return $this->isStaff($user);
    }

    /**
     * Determine whether the user can create inventory items.
     */
    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    /**
     * Determine whether the user can update the inventory item.
     */
    public function update(User $user, InventoryItem $item): bool
    {
        return $this->isStaff($user);
    }

    /**
     * Determine whether the user can delete the inventory item.
     */
    public function delete(User $user, InventoryItem $item): bool
    {
        return $this->isStaff($user);
    }

    /**
     * Determine whether the user can adjust stock levels.
     */
    public function adjust(User $user, InventoryItem $item): bool
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
