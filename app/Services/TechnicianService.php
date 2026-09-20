<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TechnicianService
{
    /**
     * Paginate technicians with optional search and status filters.
     *
     * @param  array{search?: ?string, status?: ?string}  $filters
     * @return LengthAwarePaginator<int, Technician>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Technician::with(['user', 'categories'])->withCount('assignedRequests')->latest();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return $query->paginate(15)->withQueryString();
    }

    /**
     * Create a technician user account together with the employment profile and skills.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Technician
    {
        return DB::transaction(function () use ($attributes): Technician {
            $user = User::create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => $attributes['password'],
                'phone' => $attributes['phone'] ?? null,
                'role' => UserRole::Technician,
            ]);

            $technician = $user->technician()->create([
                'phone' => $attributes['phone'] ?? null,
                'emergency_contact' => $attributes['emergency_contact'] ?? null,
                'notes' => $attributes['notes'] ?? null,
                'hired_at' => $attributes['hired_at'] ?? null,
                'is_active' => filter_var($attributes['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ]);

            $technician->categories()->sync($attributes['skills'] ?? []);

            return $technician;
        });
    }

    /**
     * Update the technician account, profile, and skills.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Technician $technician, array $attributes): Technician
    {
        return DB::transaction(function () use ($technician, $attributes): Technician {
            $technician->user->update([
                'name' => $attributes['name'],
                'phone' => $attributes['phone'] ?? null,
            ]);

            $technician->update([
                'phone' => $attributes['phone'] ?? null,
                'emergency_contact' => $attributes['emergency_contact'] ?? null,
                'notes' => $attributes['notes'] ?? null,
                'hired_at' => $attributes['hired_at'] ?? null,
                'is_active' => array_key_exists('is_active', $attributes)
                    ? filter_var($attributes['is_active'], FILTER_VALIDATE_BOOLEAN)
                    : $technician->is_active,
            ]);

            $technician->categories()->sync($attributes['skills'] ?? []);

            return $technician->refresh();
        });
    }

    /**
     * Toggle the employment status of the technician.
     */
    public function toggleStatus(Technician $technician): Technician
    {
        $technician->update(['is_active' => ! $technician->is_active]);

        return $technician->refresh();
    }

    /**
     * Remove the technician, deactivating the user account.
     *
     * Returns false when the technician still has assigned requests.
     */
    public function delete(Technician $technician): bool
    {
        if ($technician->assignedRequests()->exists()) {
            return false;
        }

        DB::transaction(function () use ($technician): void {
            $user = $technician->user;
            $technician->delete();

            if ($user !== null) {
                $user->update(['is_active' => false]);
            }
        });

        return true;
    }
}
