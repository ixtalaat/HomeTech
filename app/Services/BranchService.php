<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Exceptions\BranchException;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\City;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BranchService
{
    /**
     * City names served by active branches, ordered by branch priority.
     *
     * @return list<string>
     */
    public function servedCities(): array
    {
        return City::whereHas('branch', fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    /**
     * Paginate branches with technician and city counts.
     *
     * @return LengthAwarePaginator<int, Branch>
     */
    public function paginate(): LengthAwarePaginator
    {
        return Branch::with(['manager', 'cities'])->withCount(['technicians', 'cities'])->latest()->paginate(15);
    }

    /**
     * Create a branch with its served cities.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createBranch(array $attributes): Branch
    {
        return DB::transaction(function () use ($attributes): Branch {
            $branch = Branch::create($this->normalize($attributes));
            $this->syncCities($branch, $attributes['cities'] ?? null);
            $this->applyManagerMode($branch, $attributes);

            return $branch->refresh();
        });
    }

    /**
     * Update a branch with its served cities.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateBranch(Branch $branch, array $attributes): Branch
    {
        return DB::transaction(function () use ($branch, $attributes): Branch {
            $branch->update($this->normalize($attributes));

            if (array_key_exists('cities', $attributes)) {
                $this->syncCities($branch, $attributes['cities']);
            }

            $this->applyManagerMode($branch, $attributes);

            return $branch->refresh();
        });
    }

    /**
     * Paginate branch-manager accounts with their branches.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateManagers(): LengthAwarePaginator
    {
        return User::where('role', UserRole::Manager)->with('managedBranch')->latest()->paginate(15);
    }

    /**
     * Toggle the employment status of a branch manager.
     */
    public function toggleManagerStatus(User $manager): User
    {
        $manager->update(['is_active' => ! $manager->is_active]);

        return $manager->refresh();
    }

    /**
     * Update a manager account and move it between branches.
     *
     * An empty password keeps the current one. Passing no branch (or an
     * empty one) unassigns the account while keeping it reusable.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws BranchException
     */
    public function updateManager(User $manager, array $attributes): User
    {
        return DB::transaction(function () use ($manager, $attributes): User {
            $manager->update([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'is_active' => filter_var($attributes['is_active'] ?? $manager->is_active, FILTER_VALIDATE_BOOLEAN),
                ...(! empty($attributes['password']) ? ['password' => $attributes['password']] : []),
            ]);

            Branch::where('manager_user_id', $manager->id)->update(['manager_user_id' => null]);

            if (! empty($attributes['branch_id'])) {
                $branch = Branch::findOrFail((int) $attributes['branch_id']);
                $this->assignManager($branch, ['manager_user_id' => $manager->id]);
            }

            return $manager->refresh();
        });
    }

    /**
     * Delete a branch. Returns false when technicians or cities still use it.
     */
    public function deleteBranch(Branch $branch): bool
    {
        if ($branch->technicians()->exists() || $branch->cities()->exists()) {
            return false;
        }

        $branch->delete();

        return true;
    }

    /**
     * Assign, change, or remove the branch manager.
     *
     * Only active branch-manager accounts qualify, and one account manages
     * at most one branch at a time (unique manager_user_id). Removing keeps
     * the account for assignment elsewhere; it never demotes anyone.
     *
     * @param  array{manager_user_id?: ?int}  $attributes
     *
     * @throws BranchException
     */
    public function assignManager(Branch $branch, array $attributes): Branch
    {
        $userId = $attributes['manager_user_id'] ?? null;

        if ($userId === null || $userId === '') {
            $branch->update(['manager_user_id' => null]);

            return $branch->refresh();
        }

        $manager = User::whereKey((int) $userId)->where('role', UserRole::Manager)->first();

        if ($manager === null || ! $manager->is_active) {
            throw new BranchException(__('Only active manager accounts can manage a branch.'));
        }

        $otherBranch = Branch::where('manager_user_id', $manager->id)->whereKeyNot($branch->id)->first();

        if ($otherBranch !== null) {
            throw new BranchException(__(':name already manages the :branch branch.', ['name' => $manager->name, 'branch' => $otherBranch->name]));
        }

        $branch->update(['manager_user_id' => $manager->id]);

        return $branch->refresh();
    }

    /**
     * Create a branch-manager account and assign it to the branch.
     *
     * @param  array{name: string, email: string, password: string}  $attributes
     *
     * @throws BranchException
     */
    public function createManager(Branch $branch, array $attributes): Branch
    {
        return DB::transaction(function () use ($branch, $attributes): Branch {
            $manager = User::create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => $attributes['password'],
                'role' => UserRole::Manager,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            return $this->assignManager($branch, ['manager_user_id' => $manager->id]);
        });
    }

    /**
     * Determine whether the request belongs to the branch (served city).
     */
    public function ownsRequest(Branch $branch, MaintenanceRequest $request): bool
    {
        $city = $request->address !== null ? City::normalize((string) $request->address->city) : '';

        if ($city === '') {
            return false;
        }

        return $branch->cities()->where('name', $city)->exists();
    }

    /**
     * Resolve the active branch managed by the user, if any.
     */
    public function managedBranch(User $user): ?Branch
    {
        $branch = $user->managedBranch;

        return $branch !== null && $branch->is_active ? $branch : null;
    }

    /**
     * Resolve the active manager responsible for a city, if any.
     */
    public function managerForCity(string $city): ?User
    {
        $city = City::normalize($city);

        if ($city === '') {
            return null;
        }

        return User::where('role', UserRole::Manager)
            ->where('is_active', true)
            ->whereHas('managedBranch', fn ($branch) => $branch
                ->where('is_active', true)
                ->whereHas('cities', fn ($cities) => $cities->where('name', $city)))
            ->first();
    }

    /**
     * Branch performance overview for the manager dashboard.
     *
     * @return array<string, mixed>
     */
    public function overview(Branch $branch): array
    {
        $today = now()->format('Y-m-d');
        $technicianIds = $branch->technicians()->pluck('technicians.id');

        $cityNames = $branch->cities()->pluck('name');
        $inBranch = fn ($query) => $query->whereHas('address', fn ($address) => $address->whereIn('city', $cityNames->all()));

        return [
            'branch' => $branch,
            'technicians_count' => $branch->technicians()->where('is_active', true)->count(),
            'pending_requests' => MaintenanceRequest::where('status', RequestStatus::PendingReview)->where(fn ($query) => $inBranch($query))->count(),
            'unassigned_approved' => MaintenanceRequest::where('status', RequestStatus::Approved)->whereNull('technician_id')->where(fn ($query) => $inBranch($query))->count(),
            'active_jobs' => MaintenanceRequest::whereIn('status', [RequestStatus::TechnicianAssigned, RequestStatus::Scheduled, RequestStatus::TechnicianOnWay, RequestStatus::InProgress])->where(fn ($query) => $inBranch($query))->count(),
            'todays_appointments' => Appointment::where('date', $today)->whereIn('technician_id', $technicianIds)->where('status', '!=', AppointmentStatus::Cancelled)->orderBy('start_time')->limit(8)->get(),
            'revenue_30d' => (float) Invoice::whereNotIn('status', [InvoiceStatus::Cancelled])
                ->where('created_at', '>=', now()->subDays(30))
                ->whereHas('request.address', fn ($address) => $address->whereIn('city', $cityNames->all()))
                ->sum('paid_amount'),
            'recent_requests' => MaintenanceRequest::with(['user', 'service'])->where(fn ($query) => $inBranch($query))->latest()->limit(8)->get(),
        ];
    }

    /**
     * Apply the manager section of the branch form.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws BranchException
     */
    private function applyManagerMode(Branch $branch, array $attributes): void
    {
        $mode = $attributes['manager_mode'] ?? null;

        if ($mode === null || $mode === '' || $mode === 'none') {
            if (array_key_exists('manager_mode', $attributes)) {
                $this->assignManager($branch, ['manager_user_id' => null]);
            }

            return;
        }

        if ($mode === 'existing') {
            $this->assignManager($branch, ['manager_user_id' => $attributes['manager_user_id'] ?? null]);

            return;
        }

        $this->createManager($branch, [
            'name' => (string) ($attributes['manager_name'] ?? ''),
            'email' => (string) ($attributes['manager_email'] ?? ''),
            'password' => (string) ($attributes['manager_password'] ?? ''),
        ]);
    }

    /**
     * Normalize shared branch attributes (name, priority, status flag).
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalize(array $attributes): array
    {
        return [
            'name' => isset($attributes['name']) ? trim((string) $attributes['name']) : null,
            'priority' => isset($attributes['priority']) ? (int) $attributes['priority'] : 0,
            'is_active' => filter_var($attributes['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * Sync the served cities from a comma-separated input.
     *
     * Typing a city served elsewhere moves it to this branch, so every
     * city keeps exactly one responsible branch.
     */
    private function syncCities(Branch $branch, mixed $input): void
    {
        $names = $this->parseCities($input);
        $lower = array_map(mb_strtolower(...), $names);

        foreach ($branch->cities()->get() as $city) {
            if (! in_array(mb_strtolower($city->name), $lower, true)) {
                $city->delete();
            }
        }

        foreach ($names as $name) {
            $city = City::where('name', $name)->first();

            if ($city !== null) {
                $city->update(['branch_id' => $branch->id]);
            } else {
                $branch->cities()->create(['name' => $name]);
            }
        }
    }

    /**
     * Parse a comma-separated city input into unique trimmed names.
     *
     * @return list<string>
     */
    private function parseCities(mixed $input): array
    {
        if (! is_string($input) || trim($input) === '') {
            return [];
        }

        $names = [];
        $seen = [];

        foreach (explode(',', $input) as $part) {
            $name = trim($part);

            if ($name === '' || isset($seen[mb_strtolower($name)])) {
                continue;
            }

            $seen[mb_strtolower($name)] = true;
            $names[] = $name;
        }

        return $names;
    }
}
