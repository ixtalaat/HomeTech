<?php

use App\Enums\UserRole;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('forbids non-admins from managing services', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $service = Service::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.services.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.services.create'))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('admin.services.store'), [
            'name' => 'Test Service',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('admin.services.toggle-status', $service))
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('admin.services.destroy', $service))
        ->assertForbidden();
})->with([
    'customer' => UserRole::Customer,
    'technician' => UserRole::Technician,
]);

it('allows admin to view services list with filters', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $categoryA = ServiceCategory::factory()->create(['name' => 'Plumbing']);
    $categoryB = ServiceCategory::factory()->create(['name' => 'Electrical']);

    $serviceA = Service::factory()->create(['name' => 'Faucet Fix', 'service_category_id' => $categoryA->id, 'is_active' => true]);
    $serviceB = Service::factory()->create(['name' => 'Socket Replacement', 'service_category_id' => $categoryB->id, 'is_active' => false]);

    $this->actingAs($admin)
        ->get(route('admin.services.index'))
        ->assertOk()
        ->assertSee('Faucet Fix')
        ->assertSee('Socket Replacement');

    // Filter by Category
    $this->actingAs($admin)
        ->get(route('admin.services.index', ['category_id' => $categoryA->id]))
        ->assertOk()
        ->assertSee('Faucet Fix')
        ->assertDontSee('Socket Replacement');

    // Filter by Status
    $this->actingAs($admin)
        ->get(route('admin.services.index', ['status' => 'inactive']))
        ->assertOk()
        ->assertSee('Socket Replacement')
        ->assertDontSee('Faucet Fix');
});

it('allows admin to create a new service with valid data', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $category = ServiceCategory::factory()->create();

    $response = $this->actingAs($admin)
        ->post(route('admin.services.store'), [
            'service_category_id' => $category->id,
            'name' => 'AC Cleaning',
            'slug' => 'ac-cleaning',
            'description' => 'Comprehensive deep cleaning of coils and filters.',
            'base_price' => 350.00,
            'estimated_duration_minutes' => 75,
            'is_active' => '1',
        ]);

    $response->assertRedirect(route('admin.services.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('services', [
        'service_category_id' => $category->id,
        'name' => 'AC Cleaning',
        'slug' => 'ac-cleaning',
        'base_price' => 350.00,
        'estimated_duration_minutes' => 75,
        'is_active' => true,
    ]);
});

it('validates required fields when creating a service', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $response = $this->actingAs($admin)
        ->post(route('admin.services.store'), [
            'name' => '',
            'base_price' => -10,
        ]);

    $response->assertSessionHasErrors(['service_category_id', 'name', 'base_price', 'estimated_duration_minutes']);
});

it('allows admin to update an existing service', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Old Service Name',
        'base_price' => 100.00,
    ]);

    $response = $this->actingAs($admin)
        ->put(route('admin.services.update', $service), [
            'service_category_id' => $category->id,
            'name' => 'Updated Service Name',
            'slug' => 'updated-service-name',
            'description' => 'Updated description',
            'base_price' => 200.00,
            'estimated_duration_minutes' => 90,
            'is_active' => '0',
        ]);

    $response->assertRedirect(route('admin.services.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'name' => 'Updated Service Name',
        'base_price' => 200.00,
        'estimated_duration_minutes' => 90,
        'is_active' => false,
    ]);
});

it('allows admin to toggle service active status', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $service = Service::factory()->create(['is_active' => true]);

    $response = $this->actingAs($admin)
        ->patch(route('admin.services.toggle-status', $service));

    $response->assertSessionHas('success');
    expect($service->fresh()->is_active)->toBeFalse();

    // Toggle back to active
    $this->actingAs($admin)
        ->patch(route('admin.services.toggle-status', $service));

    expect($service->fresh()->is_active)->toBeTrue();
});

it('allows admin to delete a service', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $service = Service::factory()->create();

    $response = $this->actingAs($admin)
        ->delete(route('admin.services.destroy', $service));

    $response->assertRedirect(route('admin.services.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('services', [
        'id' => $service->id,
    ]);
});

it('stores a cover photo on create and shows it in the catalog', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $category = ServiceCategory::factory()->create();

    $this->actingAs($admin)->post(route('admin.services.store'), [
        'service_category_id' => $category->id,
        'name' => 'AC Deep Cleaning',
        'base_price' => 350,
        'estimated_duration_minutes' => 90,
        'cover_photo' => fakePngPhoto('ac.png'),
    ])->assertRedirect();

    $service = Service::where('name', 'AC Deep Cleaning')->firstOrFail();

    expect($service->cover_photo)->not->toBeNull();
    Storage::disk('public')->assertExists($service->cover_photo);

    $this->get(route('services.index'))->assertOk()->assertSee($service->coverPhotoUrl(), false);
    $this->get(route('services.show', $service->slug))->assertOk()->assertSee($service->coverPhotoUrl(), false);
});

it('replaces the cover photo on update and deletes the service photo', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $service = Service::factory()->create([
        'cover_photo' => fakePngPhoto('old.png')->store('services', 'public'),
    ]);
    $oldPath = $service->cover_photo;

    $this->actingAs($admin)->put(route('admin.services.update', $service), [
        'service_category_id' => $service->service_category_id,
        'name' => $service->name,
        'base_price' => 350,
        'estimated_duration_minutes' => 90,
        'cover_photo' => fakePngPhoto('new.png'),
    ])->assertRedirect();

    expect($service->refresh()->cover_photo)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($service->cover_photo);

    $this->actingAs($admin)->delete(route('admin.services.destroy', $service))->assertRedirect();

    Storage::disk('public')->assertMissing($service->cover_photo);
});

it('rejects non-image cover uploads', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $category = ServiceCategory::factory()->create();

    $this->actingAs($admin)->post(route('admin.services.store'), [
        'service_category_id' => $category->id,
        'name' => 'Bad Upload',
        'base_price' => 100,
        'estimated_duration_minutes' => 30,
        'cover_photo' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
    ])->assertSessionHasErrors('cover_photo');
});

it('seeds cover photos without overwriting uploads', function () {
    Storage::fake('public');

    $known = Service::factory()->create(['name' => 'Faucet & Tap Repair', 'cover_photo' => null]);
    $custom = Service::factory()->create(['name' => 'Faucet & Tap Repair fav', 'cover_photo' => 'services/custom.jpg']);
    Storage::disk('public')->put('services/custom.jpg', 'custom');

    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\ServicePhotoSeeder'])
        ->assertSuccessful();

    expect($known->refresh()->cover_photo)->toBe('services/seed-faucet.jpg');
    Storage::disk('public')->assertExists('services/seed-faucet.jpg');
    expect($custom->refresh()->cover_photo)->toBe('services/custom.jpg');
});
