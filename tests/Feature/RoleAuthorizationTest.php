<?php

use App\Enums\UserRole;
use App\Models\User;

it('forbids customers from the admin area', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Customer]))
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

it('allows administrators and managers into the admin area', function (UserRole $role) {
    $this->actingAs(User::factory()->create(['role' => $role]))
        ->get(route('admin.dashboard'))
        ->assertOk();
})->with([
    'admin' => UserRole::Admin,
    'manager' => UserRole::Manager,
]);
