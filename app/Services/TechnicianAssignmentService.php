<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\RequestStatus;
use App\Exceptions\TechnicianAssignmentException;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\City;
use App\Models\MaintenanceRequest;
use App\Models\Technician;
use App\Models\User;
use App\Notifications\JobAssigned;
use App\Notifications\JobUnassigned;
use App\Notifications\TechnicianAssignedToRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;

class TechnicianAssignmentService
{
    /**
     * Maximum maintenance requests a technician handles per day (BR-011).
     */
    public const MAX_DAILY_REQUESTS = 2;

    public function __construct(
        private RequestStatusService $transitions,
        private SchedulingService $scheduling,
        private WhatsAppService $whatsapp
    ) {}

    /**
     * Assign a technician to an approved maintenance request (BR-001) and book the slot.
     *
     * The slot defaults to the preferred date/time with the end derived from
     * the service estimated duration. A conflicting slot fails the whole
     * assignment — nothing is partially booked.
     *
     * @param  array{date?: ?string, start?: ?string, end?: ?string}  $slot
     *
     * @throws TechnicianAssignmentException
     */
    public function assign(MaintenanceRequest $request, Technician $technician, ?User $actor = null, array $slot = []): MaintenanceRequest
    {
        $this->guardAssignable($request, $technician);

        [$date, $start, $end] = $this->resolveSlot($request, $slot);

        if ($this->scheduling->hasConflict($technician->id, $date, $start, $end)) {
            throw new TechnicianAssignmentException(
                "Technician '{$technician->user->name}' already has an overlapping appointment on {$date}. Pick another technician or slot."
            );
        }

        return DB::transaction(function () use ($request, $technician, $actor, $date, $start, $end): MaintenanceRequest {
            $request->update(['technician_id' => $technician->id]);

            $this->transitions->transition(
                $request->refresh(),
                RequestStatus::TechnicianAssigned,
                $actor,
                "Assigned to {$technician->user->name}."
            );

            $this->scheduling->book($request->refresh(), $technician, $date, $start, $end, $actor);

            $request->user->notify(new TechnicianAssignedToRequest($request->refresh(), $technician->user->name));
            $technician->user->notify(new JobAssigned($request->refresh()));
            $this->whatsapp->notifyUser(
                $request->user,
                "Technician {$technician->user->name} was assigned to your request #{$request->id}."
            );

            return $request->refresh();
        });
    }

    /**
     * Reassign a request that is already assigned to another technician.
     *
     * The existing appointment moves to the new technician after a conflict
     * check, so the calendar never holds a stale booking.
     *
     * @throws TechnicianAssignmentException
     */
    public function reassign(MaintenanceRequest $request, Technician $technician, ?User $actor = null): MaintenanceRequest
    {
        if ($request->status === RequestStatus::Scheduled) {
            $this->guardTechnicianEligible($request, $technician);

            $appointment = $request->appointment;

            if ($appointment !== null && ! $appointment->isCancelled()) {
                if ($this->scheduling->hasConflict($technician->id, $appointment->date->format('Y-m-d'), $appointment->start_time, $appointment->end_time)) {
                    throw new TechnicianAssignmentException(
                        "Technician '{$technician->user->name}' already has an overlapping appointment on {$appointment->date->format('Y-m-d')}."
                    );
                }
            }

            return DB::transaction(function () use ($request, $technician, $actor, $appointment): MaintenanceRequest {
                $request->update(['technician_id' => $technician->id]);

                if ($appointment !== null) {
                    $appointment->update(['technician_id' => $technician->id]);
                }

                $request->statusHistories()->create([
                    'from_status' => RequestStatus::Scheduled->value,
                    'status' => RequestStatus::Scheduled->value,
                    'changed_by' => $actor?->id,
                    'reason' => "Reassigned to {$technician->user->name}.",
                ]);

                $request->user->notify(new TechnicianAssignedToRequest($request->refresh(), $technician->user->name));
                $technician->user->notify(new JobAssigned($request->refresh()));

                return $request->refresh();
            });
        }

        return $this->assign($request, $technician, $actor);
    }

    /**
     * Unassign the technician, cancelling the appointment and returning the request to approved.
     *
     * @throws TechnicianAssignmentException
     */
    public function unassign(MaintenanceRequest $request, ?User $actor = null): MaintenanceRequest
    {
        if (! in_array($request->status, [RequestStatus::TechnicianAssigned, RequestStatus::Scheduled, RequestStatus::TechnicianOnWay], true)) {
            throw new TechnicianAssignmentException(__('Only an assigned request can be unassigned.'));
        }

        return DB::transaction(function () use ($request, $actor): MaintenanceRequest {
            $technician = $request->technician;
            $technicianUser = $technician?->user;

            $request->update(['technician_id' => null]);

            $appointment = $request->appointment;

            if ($appointment !== null && ! $appointment->isCancelled()) {
                $appointment->update(['status' => AppointmentStatus::Cancelled]);
            }

            $this->transitions->transition(
                $request->refresh(),
                RequestStatus::Approved,
                $actor,
                'Technician unassigned; appointment cancelled.'
            );

            $technicianUser?->notify(new JobUnassigned($request->refresh()));

            return $request->refresh();
        });
    }

    /**
     * Eligible technicians annotated for the preferred slot.
     *
     * Each model carries `day_load` (blocking bookings that day) and a
     * nullable `slot_note` flagging off-duty, out-of-hours, capped, or
     * conflicting technicians — so manual assignment stays a conscious
     * choice instead of silently overloading anyone.
     *
     * @return Collection<int, Technician>
     */
    public function eligibleWithSlotStatus(MaintenanceRequest $request, ?Branch $branch = null): Collection
    {
        [$date, $start, $end] = $this->resolveSlot($request, []);
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        return $this->eligibleFor($request, $branch)
            ->loadMissing(['schedules' => fn (HasMany $query) => $query->where('day_of_week', $dayOfWeek)])
            ->each(function (Technician $technician) use ($date, $start, $end, $dayOfWeek): void {
                $technician->day_load = $this->dailyLoad($technician->id, $date);
                $technician->slot_note = $this->slotNote($technician, $date, $start, $end, $dayOfWeek);
            });
    }

    /**
     * Get technicians eligible for the given request (active + skilled for its category).
     *
     * Pass a branch to restrict the list to its technicians.
     *
     * @return Collection<int, Technician>
     */
    public function eligibleFor(MaintenanceRequest $request, ?Branch $branch = null): Collection
    {
        $categoryId = $request->service->service_category_id;

        return Technician::active()
            ->when($branch !== null, fn ($query): Builder => $query->where('technicians.branch_id', $branch->id))
            ->whereHas('user', fn ($query): Builder => $query->where('is_active', true))
            ->whereHas('categories', fn ($query): Builder => $query->where('service_categories.id', $categoryId))
            ->with('user')
            ->withCount('assignedRequests')
            ->orderBy('assigned_requests_count')
            ->get();
    }

    /**
     * Automatically assign the best available technician for an approved request.
     *
     * The preferred slot drives the search: city branch first, then other
     * active branches by priority (explicit cross-branch fallback). Within a
     * branch, technicians rank by daily load, then total workload, then id —
     * so work spreads fairly and the pick is deterministic.
     *
     * Returns the assigned technician, or a machine-readable code plus a
     * human-readable reason when nobody qualifies. The request stays
     * approved and unassigned in that case.
     *
     * @return array{technician: ?Technician, reason: ?string, code: string}
     *
     * @throws TechnicianAssignmentException
     */
    public function autoAssign(MaintenanceRequest $request, ?User $actor = null): array
    {
        if ($request->status !== RequestStatus::Approved) {
            throw new TechnicianAssignmentException(
                "Cannot auto-assign request #{$request->id} with status '{$request->status->value}'. Only approved requests can be assigned."
            );
        }

        [$date, $start, $end] = $this->resolveSlot($request, []);

        if ($date <= now()->format('Y-m-d')) {
            return [
                'technician' => null,
                'reason' => "The preferred slot ({$date} {$start}) is in the past. Update it before assigning.",
                'code' => 'past_slot',
            ];
        }

        $city = $request->address !== null ? City::normalize((string) $request->address->city) : '';

        if ($city === '') {
            return [
                'technician' => null,
                'reason' => "Request #{$request->id} has no service city. Automatic assignment needs an address city.",
                'code' => 'no_city',
            ];
        }

        $branches = $this->candidateBranches($city);

        if ($branches === []) {
            return [
                'technician' => null,
                'reason' => "No branch serves the city '{$city}'.",
                'code' => 'no_branch',
            ];
        }

        $skips = ['off_duty' => 0, 'outside_hours' => 0, 'conflict' => 0, 'daily_limit' => 0];

        return DB::transaction(function () use ($request, $actor, $branches, $city, $date, $start, $end, &$skips): array {
            foreach ($branches as $branch) {
                $ranked = $this->rankedCandidates($request, $branch->id, $date, $start, $end, $skips);

                if ($ranked->isEmpty()) {
                    continue;
                }

                // Serialize concurrent assignments on the candidate rows, then
                // re-verify under the locks with current reads before booking.
                $locked = Technician::whereIn('id', $ranked->pluck('id'))->lockForUpdate()->orderBy('id')->get()->keyBy('id');

                foreach ($ranked as $candidate) {
                    $technician = $locked->get($candidate->id);

                    if ($technician === null) {
                        continue;
                    }

                    if ($this->dailyLoad($technician->id, $date) >= self::MAX_DAILY_REQUESTS) {
                        $skips['daily_limit']++;

                        continue;
                    }

                    if ($this->hasConflictLocked($technician->id, $date, $start, $end)) {
                        $skips['conflict']++;

                        continue;
                    }

                    try {
                        $assigned = $this->assign($request->refresh(), $technician, $actor);
                    } catch (TechnicianAssignmentException) {
                        $skips['conflict']++;

                        continue;
                    }

                    return ['technician' => $assigned->technician, 'reason' => null, 'code' => 'assigned'];
                }
            }

            return ['technician' => null, 'reason' => $this->failureReason($request, $branches, $city, $date, $start, $end, $skips), 'code' => 'no_match'];
        });
    }

    /**
     * Determine whether the technician can take the given slot.
     *
     * All four gates must hold: a working-day schedule covering the window,
     * no overlapping appointment, and room under the daily limit.
     */
    public function isAvailableForSlot(Technician $technician, string $date, string $start, string $end): bool
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        $schedule = $technician->relationLoaded('schedules')
            ? $technician->schedules->firstWhere('day_of_week', $dayOfWeek)
            : $technician->schedules()->where('day_of_week', $dayOfWeek)->first();

        if ($schedule === null || ! $schedule->covers($start, $end)) {
            return false;
        }

        if ($this->dailyLoad($technician->id, $date) >= self::MAX_DAILY_REQUESTS) {
            return false;
        }

        return ! $this->scheduling->hasConflict($technician->id, $date, $start, $end);
    }

    /**
     * Count the technician's calendar-blocking appointments on a date.
     */
    public function dailyLoad(int $technicianId, string $date): int
    {
        return Appointment::forTechnicianOn($technicianId, $date)->blocking()->count();
    }

    /**
     * Short availability flag for one technician and slot, if any applies.
     */
    private function slotNote(Technician $technician, string $date, string $start, string $end, int $dayOfWeek): ?string
    {
        $schedule = $technician->schedules->firstWhere('day_of_week', $dayOfWeek);

        if ($schedule === null || ! $schedule->is_working) {
            return __('Off duty');
        }

        if (! $schedule->covers($start, $end)) {
            return __('Outside working hours');
        }

        if ($technician->day_load >= self::MAX_DAILY_REQUESTS) {
            return __('At daily limit');
        }

        if ($this->scheduling->hasConflict($technician->id, $date, $start, $end)) {
            return __('Time conflict');
        }

        return null;
    }

    /**
     * Order the branches that may serve a city: its own active branch first,
     * then every other active branch by priority (explicit fallback).
     *
     * @return array<int, Branch>
     */
    private function candidateBranches(string $city): array
    {
        $home = City::where('name', $city)->first()?->branch;

        if ($home !== null && ! $home->is_active) {
            $home = null;
        }

        $fallbacks = Branch::where('is_active', true)
            ->when($home !== null, fn ($query): Builder => $query->whereKeyNot($home->id))
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get()
            ->all();

        return $home !== null ? [$home, ...$fallbacks] : $fallbacks;
    }

    /**
     * Rank skilled, in-branch technicians for the slot: daily load, then
     * total workload, then id. Skipped technicians feed the failure reason.
     *
     * @param  array{off_duty: int, outside_hours: int, conflict: int, daily_limit: int}  $skips
     * @return BaseCollection<int, Technician>
     */
    private function rankedCandidates(
        MaintenanceRequest $request,
        int $branchId,
        string $date,
        string $start,
        string $end,
        array &$skips
    ): BaseCollection {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $categoryId = $request->service->service_category_id;

        $technicians = Technician::active()
            ->where('technicians.branch_id', $branchId)
            ->whereHas('user', fn ($query): Builder => $query->where('is_active', true))
            ->whereHas('categories', fn ($query): Builder => $query->where('service_categories.id', $categoryId))
            ->with(['user', 'schedules' => fn (HasMany $query) => $query->where('day_of_week', $dayOfWeek)])
            ->withCount('assignedRequests')
            ->get();

        $ranked = collect();

        foreach ($technicians as $technician) {
            $schedule = $technician->schedules->firstWhere('day_of_week', $dayOfWeek);

            if ($schedule === null || ! $schedule->is_working) {
                $skips['off_duty']++;

                continue;
            }

            if (! $schedule->covers($start, $end)) {
                $skips['outside_hours']++;

                continue;
            }

            if ($this->dailyLoad($technician->id, $date) >= self::MAX_DAILY_REQUESTS) {
                $skips['daily_limit']++;

                continue;
            }

            if ($this->scheduling->hasConflict($technician->id, $date, $start, $end)) {
                $skips['conflict']++;

                continue;
            }

            $ranked->push($technician);
        }

        $loads = [];

        foreach ($ranked as $technician) {
            $loads[$technician->id] = $this->dailyLoad($technician->id, $date);
        }

        return $ranked
            ->sortBy([
                fn (Technician $a, Technician $b): int => $loads[$a->id] <=> $loads[$b->id],
                fn (Technician $a, Technician $b): int => $a->assigned_requests_count <=> $b->assigned_requests_count,
                fn (Technician $a, Technician $b): int => $a->id <=> $b->id,
            ]);
    }

    /**
     * Overlap check with a locking read, so concurrent assignments serialize
     * on fresh data instead of transaction snapshots.
     */
    private function hasConflictLocked(int $technicianId, string $date, string $start, string $end): bool
    {
        return Appointment::forTechnicianOn($technicianId, $date)
            ->blocking()
            ->overlapping($start, $end)
            ->lockForUpdate()
            ->exists();
    }

    /**
     * Compose the human-readable reason shown when nobody qualifies.
     *
     * @param  array<int, Branch>  $branches
     * @param  array{off_duty: int, outside_hours: int, conflict: int, daily_limit: int}  $skips
     */
    private function failureReason(
        MaintenanceRequest $request,
        array $branches,
        string $city,
        string $date,
        string $start,
        string $end,
        array $skips
    ): string {
        $names = collect($branches)->map(fn (Branch $branch): string => "'{$branch->name}'")->implode(', ');

        $causes = [
            'off duty' => $skips['off_duty'],
            'outside working hours' => $skips['outside_hours'],
            'with conflicting appointments' => $skips['conflict'],
            'at the daily limit of '.self::MAX_DAILY_REQUESTS => $skips['daily_limit'],
        ];

        $parts = [];

        foreach ($causes as $label => $count) {
            if ($count > 0) {
                $parts[] = "{$count} {$label}";
            }
        }

        $detail = $parts === [] ? 'no skilled technicians on record' : implode(', ', $parts);

        return "No available technician in {$names} for {$city} on {$date} {$start}–{$end}: {$detail}.";
    }

    /**
     * Resolve the booking slot, defaulting to the preferred appointment.
     *
     * @param  array{date?: ?string, start?: ?string, end?: ?string}  $slot
     * @return array{string, string, string}
     */
    private function resolveSlot(MaintenanceRequest $request, array $slot): array
    {
        $date = $slot['date'] ?? $request->preferred_date->format('Y-m-d');
        $start = $slot['start'] ?? Carbon::parse($request->preferred_time)->format('H:i');

        $end = $slot['end'] ?? Carbon::parse($start)
            ->addMinutes($request->service->estimated_duration_minutes)
            ->format('H:i');

        return [$date, $start, $end];
    }

    /**
     * Guard that the request is in an assignable state and the technician is eligible.
     *
     * @throws TechnicianAssignmentException
     */
    private function guardAssignable(MaintenanceRequest $request, Technician $technician): void
    {
        if ($request->status !== RequestStatus::Approved) {
            throw new TechnicianAssignmentException(
                "Cannot assign technician to request #{$request->id} with status '{$request->status->value}'. Only approved requests can be assigned."
            );
        }

        $this->guardTechnicianEligible($request, $technician);
    }

    /**
     * Guard that the technician is active and supports the request category (BR-001).
     *
     * @throws TechnicianAssignmentException
     */
    private function guardTechnicianEligible(MaintenanceRequest $request, Technician $technician): void
    {
        if (! $technician->is_active || $technician->user === null || ! $technician->user->is_active) {
            throw new TechnicianAssignmentException(__('Cannot assign an inactive technician.'));
        }

        $category = $request->service->category;

        if ($category === null || ! $technician->supportsCategory($category->id)) {
            throw new TechnicianAssignmentException(
                "Technician '{$technician->user->name}' does not support the '{$request->service->name}' service category."
            );
        }
    }
}
