<?php

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Notifications\StaleAssignmentNudge;
use Illuminate\Support\Facades\Notification;

function staleApprovedRequest(string $city = 'Riyadh', int $daysOld = 2): MaintenanceRequest
{
    $request = mondayRequest(hourlyService(), $city);
    $request->forceFill([
        'status' => RequestStatus::Approved,
        'technician_id' => null,
        'updated_at' => now()->subDays($daysOld),
    ])->save();

    return $request->refresh();
}

it('nudges the responsible branch manager about stale assignments', function () {
    Notification::fake();

    ['manager' => $manager] = managedBranch('Riyadh');
    $request = staleApprovedRequest('Riyadh');

    $this->artisan('app:nudge-stale-assignments')
        ->assertSuccessful()
        ->expectsOutputToContain('Nudged 1');

    Notification::assertSentTo($manager, StaleAssignmentNudge::class);

    // Re-running while still stale stays quiet (one nudge per episode).
    Notification::fake();

    $this->artisan('app:nudge-stale-assignments')->assertSuccessful();

    Notification::assertNothingSent();
});

it('falls back to admins when no branch is responsible', function () {
    Notification::fake();

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    staleApprovedRequest('Nowhere');

    $this->artisan('app:nudge-stale-assignments')->assertSuccessful();

    Notification::assertSentTo($admin, StaleAssignmentNudge::class);
});

it('ignores fresh, assigned and non-approved requests', function () {
    Notification::fake();

    User::factory()->create(['role' => UserRole::Admin]);
    managedBranch('Riyadh');

    $service = hourlyService();

    $fresh = mondayRequest($service, 'Riyadh');
    $fresh->forceFill(['status' => RequestStatus::Approved, 'technician_id' => null, 'updated_at' => now()])->save();

    $technician = workingTechnician($service, staffedBranch('Jeddah'));
    $assigned = mondayRequest($service, 'Riyadh');
    $assigned->forceFill([
        'status' => RequestStatus::Scheduled,
        'technician_id' => $technician->id,
        'updated_at' => now()->subDays(3),
    ])->save();

    $pending = mondayRequest($service, 'Riyadh');
    $pending->forceFill(['status' => RequestStatus::PendingReview, 'updated_at' => now()->subDays(3)])->save();

    $this->artisan('app:nudge-stale-assignments')
        ->assertSuccessful()
        ->expectsOutputToContain('Nudged 0');

    Notification::assertNothingSent();
});
