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

it('finds services by category name in either language', function () {
    $category = ServiceCategory::factory()->create(['name' => 'Plumbing', 'slug' => 'plumbing', 'is_active' => true]);
    $category->saveTranslations(['ar' => ['name' => 'سباكة']]);
    Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Faucet Leak Fix',
        'description' => 'Fixes leaks fast.',
        'is_active' => true,
    ]);

    $this->get(route('services.index', ['search' => 'Plumbing']))
        ->assertOk()
        ->assertSee('Faucet Leak Fix');

    $this->get(route('services.index', ['search' => 'سباكة']))
        ->assertOk()
        ->assertSee('Faucet Leak Fix');
});

it('finds services by translated description', function () {
    $category = ServiceCategory::factory()->create(['is_active' => true]);
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Drain Service',
        'description' => 'Clears blockages.',
        'is_active' => true,
    ]);
    $service->saveTranslations(['ar' => ['name' => 'خدمة المجاري', 'description' => 'تسليك المجاري المسدودة بسرعة.']]);

    $this->get(route('services.index', ['search' => 'المسدودة']))
        ->assertOk()
        ->assertSee('Drain Service');
});

it('matches inflected Arabic search terms via light stemming', function () {
    $category = ServiceCategory::factory()->create(['is_active' => true]);
    $faucet = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Tap Repair',
        'description' => 'Fixes taps.',
        'is_active' => true,
    ]);
    $faucet->saveTranslations(['ar' => ['name' => 'تصليح الحنفيات', 'description' => 'إصلاح الحنفيات والخلاطات.']]);
    $tech = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Tech Visit',
        'description' => 'A technician visit.',
        'is_active' => true,
    ]);
    $tech->saveTranslations(['ar' => ['name' => 'زيارة فني الصيانة']]);
    $other = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Door Painting',
        'description' => 'Paints doors.',
        'is_active' => true,
    ]);
    $other->saveTranslations(['ar' => ['name' => 'دهان الأبواب']]);

    // Singular ة-form finds the plural ت-form.
    $this->get(route('services.index', ['search' => 'حنفية']))
        ->assertOk()
        ->assertSee('Tap Repair')
        ->assertDontSee('Door Painting');

    // Prefixed and suffixed form finds the bare form.
    $this->get(route('services.index', ['search' => 'للفنيين']))
        ->assertOk()
        ->assertSee('Tech Visit')
        ->assertDontSee('Door Painting');

    // Bare alef finds the hamza form.
    $this->get(route('services.index', ['search' => 'اصلاح']))
        ->assertOk()
        ->assertSee('Tap Repair');
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

it('collapses catalog nav links on small screens', function () {
    $this->get(route('services.index'))
        ->assertOk()
        ->assertSee('hidden rounded-xl px-4 py-2', false);
});

it('exposes SEO tags on catalog and detail pages', function () {
    $category = ServiceCategory::factory()->create(['is_active' => true]);
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Appliance Maintenance',
        'slug' => 'appliance-maintenance',
        'description' => 'Keeps every appliance running smoothly.',
        'is_active' => true,
    ]);

    $this->get(route('services.index'))
        ->assertOk()
        ->assertSee('property="og:title"', false)
        ->assertSee('rel="canonical"', false)
        ->assertSee(route('services.index'), false);

    $this->get(route('services.show', 'appliance-maintenance'))
        ->assertOk()
        ->assertSee('property="og:title"', false)
        ->assertSee('Appliance Maintenance', false)
        ->assertSee(route('services.show', 'appliance-maintenance'), false);
});
