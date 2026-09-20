<?php

use App\Enums\UserRole;
use App\Models\ServiceCategory;
use App\Models\Technician;
use App\Models\User;

it('forbids non-staff from managing technicians', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $technician = Technician::factory()->create();

    $this->actingAs($user)->get(route('admin.technicians.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.technicians.create'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.technicians.store'), [])->assertForbidden();
    $this->actingAs($user)->patch(route('admin.technicians.toggle-status', $technician))->assertForbidden();
})->with([
    'customer' => UserRole::Customer,
    'technician' => UserRole::Technician,
]);

it('creates a technician with user account and skills', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $plumbing = ServiceCategory::factory()->create(['name' => 'Plumbing']);
    $electrical = ServiceCategory::factory()->create(['name' => 'Electrical']);

    $this->actingAs($admin)->post(route('admin.technicians.store'), [
        'name' => 'Ahmed Hassan',
        'email' => 'ahmed@hometech.test',
        'password' => 'password123',
        'phone' => '01000000001',
        'skills' => [$plumbing->id, $electrical->id],
    ])->assertRedirect();

    $technician = Technician::first();
    expect($technician->user->role)->toBe(UserRole::Technician)
        ->and($technician->user->email)->toBe('ahmed@hometech.test')
        ->and($technician->categories->pluck('id')->sort()->values()->all())->toBe(
            collect([$plumbing->id, $electrical->id])->sort()->values()->all()
        );
});

it('updates technician skills via sync', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $plumbing = ServiceCategory::factory()->create();
    $electrical = ServiceCategory::factory()->create();
    $technician = Technician::factory()->create();
    $technician->categories()->sync([$plumbing->id]);

    $this->actingAs($admin)->put(route('admin.technicians.update', $technician), [
        'name' => $technician->user->name,
        'skills' => [$electrical->id],
    ])->assertRedirect();

    expect($technician->categories()->pluck('service_categories.id')->all())->toBe([$electrical->id]);
});

it('toggles technician status and blocks deletion with assigned requests', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $technician = Technician::factory()->create();

    $this->actingAs($admin)->patch(route('admin.technicians.toggle-status', $technician))->assertRedirect();

    expect($technician->refresh()->is_active)->toBeFalse();
});
