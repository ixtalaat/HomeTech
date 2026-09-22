<?php

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Technician;
use App\Models\User;
use Carbon\Carbon;

it('shows the dispatcher week with bookings', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $technician = Technician::factory()->create();
    $monday = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

    $appointment = Appointment::factory()->create([
        'technician_id' => $technician->id,
        'date' => $monday,
        'start_time' => '10:00',
        'end_time' => '11:00',
    ]);

    $this->actingAs($admin)->get(route('admin.reports.calendar'))
        ->assertOk()
        ->assertSee($technician->user->name, false)
        ->assertSee((string) $appointment->maintenance_request_id, false);
});

it('scopes the calendar to one branch', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $monday = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

    $riyadh = staffedBranch('Riyadh');
    $jeddah = staffedBranch('Jeddah');
    $riyadhTech = Technician::factory()->create(['branch_id' => $riyadh->id]);
    $riyadhTech->user->update(['name' => 'Riyadh Test Tech']);
    $jeddahTech = Technician::factory()->create(['branch_id' => $jeddah->id]);
    $jeddahTech->user->update(['name' => 'Jeddah Test Tech']);

    Appointment::factory()->create(['technician_id' => $riyadhTech->id, 'date' => $monday]);
    Appointment::factory()->create(['technician_id' => $jeddahTech->id, 'date' => $monday]);

    $response = $this->actingAs($admin)->get(route('admin.reports.calendar', ['branch_id' => $riyadh->id]))
        ->assertOk();

    $response->assertSee('Riyadh Test Tech', false)
        ->assertDontSee('Jeddah Test Tech', false);
});

it('navigates between weeks', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $technician = Technician::factory()->create();
    $nextMonday = Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeek()->format('Y-m-d');

    $appointment = Appointment::factory()->create([
        'technician_id' => $technician->id,
        'date' => $nextMonday,
        'start_time' => '10:00',
        'end_time' => '11:00',
    ]);

    // Next week's booking is hidden on the current week...
    $this->actingAs($admin)->get(route('admin.reports.calendar'))
        ->assertOk()
        ->assertDontSee('/admin/requests/'.$appointment->maintenance_request_id, false);

    // ...and visible one week ahead.
    $this->actingAs($admin)->get(route('admin.reports.calendar', ['week' => 1]))
        ->assertOk()
        ->assertSee((string) $appointment->maintenance_request_id, false);
});
