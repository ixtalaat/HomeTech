<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ServiceCategory;
use App\Models\User;

class ServiceCategoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, ServiceCategory $serviceCategory): bool
    {
        return $serviceCategory->is_active || ($user !== null && in_array($user->role, [UserRole::Admin, UserRole::Manager], true));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager], true);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ServiceCategory $serviceCategory): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager], true);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ServiceCategory $serviceCategory): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager], true);
    }
}
