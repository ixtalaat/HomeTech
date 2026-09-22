<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\QueueBacklogStuck;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

function seedStuckJobs(int $count, int $ageMinutes): void
{
    for ($i = 0; $i < $count; $i++) {
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'stuck-job']),
            'attempts' => 0,
            'available_at' => now()->subMinutes($ageMinutes)->timestamp,
            'created_at' => now()->subMinutes($ageMinutes)->timestamp,
        ]);
    }
}

it('alerts admins once per stuck backlog episode', function () {
    Notification::fake();

    $admin = User::factory()->create(['role' => UserRole::Admin]);

    seedStuckJobs(60, 45);

    $this->artisan('app:check-queue-backlog')
        ->assertSuccessful()
        ->expectsOutputToContain('Backlog alert sent');

    Notification::assertSentTo($admin, QueueBacklogStuck::class);

    // Second run while still stuck stays quiet (no duplicate pages).
    Notification::fake();

    $this->artisan('app:check-queue-backlog')->assertSuccessful();

    Notification::assertNothingSent();
});

it('stays quiet on a healthy or flowing queue', function () {
    Notification::fake();

    User::factory()->create(['role' => UserRole::Admin]);

    // Empty queue clears any previous alert flag.
    $this->artisan('app:check-queue-backlog')
        ->assertSuccessful()
        ->expectsOutputToContain('healthy');

    Notification::assertNothingSent();

    // A few fresh jobs are normal flow, not an outage.
    seedStuckJobs(3, 5);

    $this->artisan('app:check-queue-backlog')
        ->assertSuccessful()
        ->expectsOutputToContain('flowing');

    Notification::assertNothingSent();
});

it('alerts on an old job even when the backlog is small', function () {
    Notification::fake();

    $admin = User::factory()->create(['role' => UserRole::Admin]);

    seedStuckJobs(1, 90);

    $this->artisan('app:check-queue-backlog')->assertSuccessful();

    Notification::assertSentTo($admin, QueueBacklogStuck::class);
});
