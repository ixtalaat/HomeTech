<?php

namespace App\Console\Commands;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Notifications\StaleAssignmentNudge;
use App\Services\BranchService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class NudgeStaleAssignments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:nudge-stale-assignments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nudge branch managers about approved requests waiting over a day for assignment';

    /**
     * Execute the console command.
     */
    public function handle(BranchService $branches): int
    {
        $stale = MaintenanceRequest::with('address')
            ->where('status', RequestStatus::Approved)
            ->whereNull('technician_id')
            ->where('updated_at', '<', now()->subDay())
            ->orderBy('updated_at')
            ->get()
            ->reject(fn (MaintenanceRequest $request): bool => Cache::has($this->flag($request)));

        $nudged = 0;

        foreach ($stale as $request) {
            $recipients = $this->recipients($branches, $request);

            if ($recipients->isEmpty()) {
                continue;
            }

            Notification::send($recipients, new StaleAssignmentNudge($request));
            Cache::put($this->flag($request), true, now()->addDays(7));
            $nudged++;
        }

        $this->info("Nudged {$nudged} stale unassigned request(s).");

        return self::SUCCESS;
    }

    /**
     * Resolve who to nudge: the responsible branch manager, else active admins.
     *
     * @return Collection<int, User>
     */
    private function recipients(BranchService $branches, MaintenanceRequest $request): Collection
    {
        $manager = $branches->managerForCity((string) $request->address?->city);

        if ($manager !== null) {
            return collect([$manager]);
        }

        return User::whereIn('role', [UserRole::Admin, UserRole::Manager])
            ->where('is_active', true)
            ->get();
    }

    private function flag(MaintenanceRequest $request): string
    {
        return "stale-assignment-nudged-{$request->id}";
    }
}
