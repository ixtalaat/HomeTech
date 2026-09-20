<?php

use App\Enums\UserRole;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;

it('forbids guests from accessing admin categories', function () {
    $this->get(route('admin.categories.index'))
        ->assertRedirect(route('login'));
});

it('forbids customer and technician from accessing admin categories', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->get(route('admin.categories.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.categories.create'))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('admin.categories.store'), ['name' => 'Plumbing'])
        ->assertForbidden();
})->with([
    'customer' => UserRole::Customer,
    'technician' => UserRole::Technician,
]);

it('allows admin and manager to view categories list', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $category = ServiceCategory::factory()->create(['name' => 'Plumbing']);

    $this->actingAs($user)
        ->get(route('admin.categories.index'))
        ->assertOk()
        ->assertSee('Plumbing');
})->with([
    'admin' => UserRole::Admin,
    'manager' => UserRole::Manager,
]);

it('allows admin to create a new category', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $response = $this->actingAs($admin)
        ->post(route('admin.categories.store'), [
            'name' => 'Electrical Services',
            'slug' => 'electrical-services',
            'description' => 'Fix wires and outlets',
            'icon' => 'zap',
            'is_active' => '1',
        ]);

    $response->assertRedirect(route('admin.categories.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('service_categories', [
        'name' => 'Electrical Services',
        'slug' => 'electrical-services',
        'description' => 'Fix wires and outlets',
        'icon' => 'zap',
        'is_active' => true,
    ]);
});

it('validates unique category names on creation', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    ServiceCategory::factory()->create(['name' => 'Painting']);

    $response = $this->actingAs($admin)
        ->post(route('admin.categories.store'), [
            'name' => 'Painting',
        ]);

    $response->assertSessionHasErrors('name');
});

it('allows admin to update an existing category', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $category = ServiceCategory::factory()->create([
        'name' => 'Old Category Name',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)
        ->put(route('admin.categories.update', $category), [
            'name' => 'Updated Category Name',
            'slug' => 'updated-category-name',
            'description' => 'New description',
            'icon' => 'wrench',
            'is_active' => '0',
        ]);

    $response->assertRedirect(route('admin.categories.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('service_categories', [
        'id' => $category->id,
        'name' => 'Updated Category Name',
        'slug' => 'updated-category-name',
        'is_active' => false,
    ]);
});

it('allows deleting a category without services', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $category = ServiceCategory::factory()->create();

    $response = $this->actingAs($admin)
        ->delete(route('admin.categories.destroy', $category));

    $response->assertRedirect(route('admin.categories.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('service_categories', [
        'id' => $category->id,
    ]);
});

it('prevents deleting a category that has associated services', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $category = ServiceCategory::factory()->create();
    Service::factory()->create(['service_category_id' => $category->id]);

    $response = $this->actingAs($admin)
        ->delete(route('admin.categories.destroy', $category));

    $response->assertRedirect(route('admin.categories.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('service_categories', [
        'id' => $category->id,
    ]);
});
