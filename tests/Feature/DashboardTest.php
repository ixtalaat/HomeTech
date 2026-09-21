<?php

use App\Enums\UserRole;
use App\Models\MaintenanceRequest;
use App\Models\Technician;
use App\Models\User;
use App\Services\ReportingService;

it('routes each role to its own dashboard', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $tech = Technician::factory()->create();
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
    $this->actingAs($tech->user)->get(route('dashboard'))->assertOk()->assertSee('Ready for today');
    $this->actingAs($customer)->get(route('dashboard'))->assertOk()->assertSee('Stay ahead of every repair');
});

it('shows customers only their own numbers', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $other = User::factory()->create(['role' => UserRole::Customer]);

    MaintenanceRequest::factory()->approved()->create(['user_id' => $customer->id]);
    MaintenanceRequest::factory()->create(['user_id' => $other->id]);
    MaintenanceRequest::factory()->create(['user_id' => $other->id]);

    $overview = app(ReportingService::class)->customerOverview($customer);

    expect($overview['active_services'])->toBe(1)
        ->and($overview['completed_jobs'])->toBe(0);

    $this->actingAs($customer)->get(route('dashboard'))->assertOk()->assertSee('Active services');
});

it('shows technicians only their own jobs', function () {
    $tech = Technician::factory()->create();
    $request = scheduledRequest();
    $request->update(['technician_id' => $tech->id]);
    $request->appointment->update(['technician_id' => $tech->id]);

    $this->actingAs($tech->user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee((string) $request->id);
});
