<?php

use App\Enums\UserRole;
use App\Models\User;

it('forbids non-staff from managing customers', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($user)->get(route('admin.customers.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.customers.show', $customer))->assertForbidden();
    $this->actingAs($user)->get(route('admin.customers.edit', $customer))->assertForbidden();
    $this->actingAs($user)->put(route('admin.customers.update', $customer), ['name' => 'X'])->assertForbidden();
    $this->actingAs($user)->patch(route('admin.customers.toggle-status', $customer))->assertForbidden();
})->with([
    'customer' => UserRole::Customer,
    'technician' => UserRole::Technician,
]);

it('allows admin to list, view, update, and toggle customer status', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $customer = User::factory()->create(['role' => UserRole::Customer, 'name' => 'Sara Ahmed']);

    $this->actingAs($admin)->get(route('admin.customers.index'))->assertOk()->assertSee('Sara Ahmed');

    $this->actingAs($admin)->get(route('admin.customers.show', $customer))->assertOk()->assertSee('Sara Ahmed');

    $this->actingAs($admin)->put(route('admin.customers.update', $customer), [
        'name' => 'Sara Updated',
        'phone' => '01000000000',
    ])->assertRedirect(route('admin.customers.show', $customer));

    expect($customer->refresh()->name)->toBe('Sara Updated');

    $this->actingAs($admin)->patch(route('admin.customers.toggle-status', $customer))->assertRedirect();

    expect($customer->refresh()->is_active)->toBeFalse();
});

it('returns 404 when admin accesses a non-customer profile', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $technician = User::factory()->create(['role' => UserRole::Technician]);

    $this->actingAs($admin)->get(route('admin.customers.show', $technician))->assertNotFound();
    $this->actingAs($admin)->get(route('admin.customers.edit', $technician))->assertNotFound();
});

it('prevents customers from accessing other profiles via admin routes', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $other = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($customer)->get(route('admin.customers.show', $other))->assertForbidden();
    $this->actingAs($customer)->get(route('profile.edit'))->assertOk();
});
