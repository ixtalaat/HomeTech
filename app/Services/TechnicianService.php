<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Exceptions\ScheduleException;
use App\Models\Branch;
use App\Models\Technician;
use App\Models\TechnicianSchedule;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TechnicianService
{
    /**
     * Paginate technicians with optional search and status filters.
     *
     * Pass a branch to scope the listing to its technicians.
     *
     * @param  array{search?: ?string, status?: ?string}  $filters
     * @return LengthAwarePaginator<int, Technician>
     */
    public function paginate(array $filters, ?Branch $branch = null): LengthAwarePaginator
    {
        $query = Technician::with(['user', 'categories'])->withCount('assignedRequests')->latest();

        if ($branch !== null) {
            $query->where('branch_id', $branch->id);
        }

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
                'branch_id' => $attributes['branch_id'] ?? null,
                'phone' => $attributes['phone'] ?? null,
                'emergency_contact' => $attributes['emergency_contact'] ?? null,
                'notes' => $attributes['notes'] ?? null,
                'hired_at' => $attributes['hired_at'] ?? null,
                'is_active' => filter_var($attributes['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ]);

            $technician->schedules()->createMany(TechnicianSchedule::defaultWeek());

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
                'branch_id' => array_key_exists('branch_id', $attributes)
                    ? $attributes['branch_id']
                    : $technician->branch_id,
                'is_active' => array_key_exists('is_active', $attributes)
                    ? filter_var($attributes['is_active'], FILTER_VALIDATE_BOOLEAN)
                    : $technician->is_active,
            ]);

            $technician->categories()->sync($attributes['skills'] ?? []);

            return $technician->refresh();
        });
    }

    /**
     * Replace the technician's weekly schedule (one row per weekday).
     *
     * Working days require a start before the end; days off drop their times.
     *
     * @param  array<int, array{day_of_week: int, is_working?: mixed, start_time?: ?string, end_time?: ?string}>  $days
     *
     * @throws ScheduleException
     */
    public function syncSchedule(Technician $technician, array $days): Technician
    {
        $seen = [];

        foreach ($days as $day) {
            $dayOfWeek = (int) ($day['day_of_week'] ?? -1);

            if ($dayOfWeek < 0 || $dayOfWeek > 6 || isset($seen[$dayOfWeek])) {
                throw new ScheduleException(__('Each weekday 0–6 must appear exactly once.'));
            }

            $seen[$dayOfWeek] = true;

            $working = filter_var($day['is_working'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (! $working) {
                continue;
            }

            $start = $day['start_time'] ?? null;
            $end = $day['end_time'] ?? null;

            if ($start === null || $end === null || $end <= $start) {
                throw new ScheduleException(__('Working days need a start time before the end time.'));
            }
        }

        return DB::transaction(function () use ($technician, $days): Technician {
            $technician->schedules()->delete();

            foreach ($days as $day) {
                $working = filter_var($day['is_working'] ?? false, FILTER_VALIDATE_BOOLEAN);

                $technician->schedules()->create([
                    'day_of_week' => (int) $day['day_of_week'],
                    'is_working' => $working,
                    'start_time' => $working ? $day['start_time'] : null,
                    'end_time' => $working ? $day['end_time'] : null,
                ]);
            }

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
