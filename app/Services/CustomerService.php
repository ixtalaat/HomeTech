<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerService
{
    /**
     * Paginate customers with optional search and status filters.
     *
     * @param  array{search?: ?string, status?: ?string}  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = User::customers()->withCount('addresses')->latest();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return $query->paginate(15)->withQueryString();
    }

    /**
     * Update the customer profile fields.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $customer, array $attributes): User
    {
        $customer->update([
            'name' => $attributes['name'],
            'phone' => $attributes['phone'] ?? null,
            'is_active' => filter_var($attributes['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ]);

        return $customer->refresh();
    }

    /**
     * Toggle the customer account status.
     */
    public function toggleStatus(User $customer): User
    {
        $customer->update(['is_active' => ! $customer->is_active]);

        return $customer->refresh();
    }
}
