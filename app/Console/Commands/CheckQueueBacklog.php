<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\QueueBacklogStuck;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CheckQueueBacklog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-queue-backlog';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Alert admins when queued jobs pile up unprocessed (stuck worker)';

    /**
     * Backlog size that always alerts, regardless of age.
     */
    private const PENDING_THRESHOLD = 50;

    /**
     * Oldest-job age in minutes that always alerts, regardless of size.
     */
    private const STALE_MINUTES = 30;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $pending = (int) DB::table('jobs')->count();
        $oldest = DB::table('jobs')->min('created_at');

        if ($pending === 0) {
            Cache::forget('queue-backlog-alerted');

            $this->info('Queue is healthy.');

            return self::SUCCESS;
        }

        $oldestMinutes = $oldest !== null ? Carbon::parse($oldest)->diffInMinutes(now()) : 0;
        $stuck = $pending >= self::PENDING_THRESHOLD || $oldestMinutes >= self::STALE_MINUTES;

        if (! $stuck) {
            $this->info("Queue flowing ({$pending} pending).");

            return self::SUCCESS;
        }

        Log::warning('Queue backlog detected.', ['pending' => $pending, 'oldest_minutes' => $oldestMinutes]);

        // Alert once per episode so an overnight outage pages once, not hourly.
        if (Cache::get('queue-backlog-alerted')) {
            $this->warn('Backlog already alerted; still stuck.');

            return self::SUCCESS;
        }

        $admins = User::where('role', UserRole::Admin)->where('is_active', true)->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new QueueBacklogStuck($pending, $oldestMinutes));
        }

        Cache::put('queue-backlog-alerted', true, now()->addDay());

        $this->error("Backlog alert sent ({$pending} pending, oldest {$oldestMinutes}m).");

        return self::SUCCESS;
    }
}
