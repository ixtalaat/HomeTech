<?php

use App\Enums\UserRole;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;

it('allows guests and customers to view the public services catalog', function () {
    $category = ServiceCategory::factory()->create(['name' => 'Plumbing', 'is_active' => true]);
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Faucet Leak Fix',
        'is_active' => true,
    ]);

    // As Guest
    $this->get(route('services.index'))
        ->assertOk()
        ->assertSee('Faucet Leak Fix')
        ->assertSee('Plumbing');

    // As Customer
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $this->actingAs($customer)
        ->get(route('services.index'))
        ->assertOk()
        ->assertSee('Faucet Leak Fix');
});

it('filters out inactive services from the public catalog', function () {
    $category = ServiceCategory::factory()->create(['is_active' => true]);
    $activeService = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Active AC Tuneup',
        'is_active' => true,
    ]);
    $inactiveService = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Inactive Old AC Service',
        'is_active' => false,
    ]);

    $this->get(route('services.index'))
        ->assertOk()
        ->assertSee('Active AC Tuneup')
        ->assertDontSee('Inactive Old AC Service');
});

it('filters out services belonging to inactive categories', function () {
    $activeCategory = ServiceCategory::factory()->create(['name' => 'Active Cat', 'is_active' => true]);
    $inactiveCategory = ServiceCategory::factory()->create(['name' => 'Inactive Cat', 'is_active' => false]);

    $serviceInActiveCat = Service::factory()->create([
        'service_category_id' => $activeCategory->id,
        'name' => 'Visible Service',
        'is_active' => true,
    ]);

    $serviceInInactiveCat = Service::factory()->create([
        'service_category_id' => $inactiveCategory->id,
        'name' => 'Hidden Service',
        'is_active' => true,
    ]);

    $this->get(route('services.index'))
        ->assertOk()
        ->assertSee('Visible Service')
        ->assertDontSee('Hidden Service');
});

it('allows filtering services by category', function () {
    $plumbing = ServiceCategory::factory()->create(['name' => 'Plumbing', 'slug' => 'plumbing', 'is_active' => true]);
    $electrical = ServiceCategory::factory()->create(['name' => 'Electrical', 'slug' => 'electrical', 'is_active' => true]);

    $plumbingService = Service::factory()->create([
        'service_category_id' => $plumbing->id,
        'name' => 'Pipe Unclogging',
        'is_active' => true,
    ]);

    $electricalService = Service::factory()->create([
        'service_category_id' => $electrical->id,
        'name' => 'Fuse Repair',
        'is_active' => true,
    ]);

    $this->get(route('services.index', ['category' => 'plumbing']))
        ->assertOk()
        ->assertSee('Pipe Unclogging')
        ->assertDontSee('Fuse Repair');
});

it('allows searching services by keywords', function () {
    $category = ServiceCategory::factory()->create(['is_active' => true]);
    $serviceA = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Heater Installation',
        'is_active' => true,
    ]);
    $serviceB = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Door Painting',
        'is_active' => true,
    ]);

    $this->get(route('services.index', ['search' => 'Heater']))
        ->assertOk()
        ->assertSee('Heater Installation')
        ->assertDontSee('Door Painting');
});

it('allows viewing an active service detail page', function () {
    $category = ServiceCategory::factory()->create(['is_active' => true]);
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Appliance Maintenance',
        'slug' => 'appliance-maintenance',
        'base_price' => 420.00,
        'estimated_duration_minutes' => 60,
        'is_active' => true,
    ]);

    $this->get(route('services.show', 'appliance-maintenance'))
        ->assertOk()
        ->assertSee('Appliance Maintenance')
        ->assertSee('420.00')
        ->assertSee('60 minutes');
});

it('returns 404 when viewing an inactive service', function () {
    $category = ServiceCategory::factory()->create(['is_active' => true]);
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Decommissioned Service',
        'slug' => 'decommissioned-service',
        'is_active' => false,
    ]);

    $this->get(route('services.show', 'decommissioned-service'))
        ->assertNotFound();
});
