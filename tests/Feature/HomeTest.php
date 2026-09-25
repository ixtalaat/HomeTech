<?php

use App\Models\Branch;
use App\Models\City;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::flush();
});

it('shows popular services with photos on the home page', function () {
    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'is_active' => true,
        'cover_photo' => 'services/demo.jpg',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Popular services', false)
        ->assertSee($service->name, false)
        ->assertSee('/storage/services/demo.jpg', false);
});

it('spotlights live platform numbers in the hero card', function () {
    $category = ServiceCategory::factory()->create(['name' => 'Plumbing', 'is_active' => true]);
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Live Hero Service',
        'base_price' => 199.00,
        'is_active' => true,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Tomorrow')
        ->assertDontSee('Ahmed M.')
        ->assertSee('Live Hero Service', false)
        ->assertSee('199.00', false);
});

it('hides the popular strip when no services exist', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Popular services', false)
        ->assertDontSee('Popular right now', false);
});

it('renders the home page in Arabic', function () {
    $category = ServiceCategory::factory()->create();
    Service::factory()->create([
        'service_category_id' => $category->id,
        'is_active' => true,
    ]);

    $this->withSession(['locale' => 'ar'])->get(route('home'))
        ->assertOk()
        ->assertSee('الخدمات الأكثر طلبًا', false);
});

it('shows a catalog search form in the hero', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('services.index'), false)
        ->assertSee('What do you need fixed?', false);
});

it('shows top-rated reviews as testimonials and skips the rest', function () {
    Review::factory()->create(['rating' => 5, 'comment' => 'Spotless work, highly recommended!']);
    Review::factory()->create(['rating' => 2, 'comment' => 'Mediocre visit, would not rebook.']);
    Review::factory()->create(['rating' => 5, 'comment' => null]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('What our customers say', false)
        ->assertSee('Spotless work, highly recommended!', false)
        ->assertSee('Reviews', false)
        ->assertDontSee('Mediocre visit, would not rebook.', false);
});

it('hides testimonials when no reviewed jobs exist', function () {
    expect(Review::count())->toBe(0);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('What our customers say', false);
});

it('shows quick category shortcuts under the hero search', function () {
    $plumbing = ServiceCategory::factory()->create(['name' => 'Plumbing', 'slug' => 'plumbing', 'is_active' => true]);
    Service::factory()->create(['service_category_id' => $plumbing->id, 'is_active' => true]);
    ServiceCategory::factory()->create(['name' => 'Ghost Trade', 'slug' => 'ghost-trade', 'is_active' => false]);
    ServiceCategory::factory()->create(['name' => 'Empty Trade', 'slug' => 'empty-trade', 'is_active' => true]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Popular right now', false)
        ->assertSee('Plumbing', false)
        ->assertSee(route('services.index', ['category' => 'plumbing']), false)
        ->assertDontSee('Ghost Trade', false)
        ->assertDontSee('Empty Trade', false);
});

it('shows the coverage band, stats, and FAQ', function () {
    $branch = Branch::factory()->create(['is_active' => true]);
    City::factory()->create(['branch_id' => $branch->id, 'name' => 'Riyadh']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Now serving', false)
        ->assertSee('Riyadh', false)
        ->assertSee('Average rating', false)
        ->assertSee('Frequently asked questions', false)
        ->assertSee('How do I book a service?', false);
});

it('exposes SEO and social meta tags on the home page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<meta name="description"', false)
        ->assertSee('property="og:title"', false)
        ->assertSee('property="og:description"', false)
        ->assertSee('rel="canonical"', false)
        ->assertSee(route('home'), false);

    $this->withSession(['locale' => 'ar'])->get(route('home'))
        ->assertOk()
        ->assertSee('واستمتع بمنزلك', false);
});

it('collapses public nav links on small screens', function () {
    $this->get(route('home'))->assertOk()->assertSee('hidden rounded-xl px-4 py-2.5', false);
    $this->get(route('about'))->assertOk()->assertSee('hidden rounded-xl px-4 py-2.5', false);
    $this->get(route('contact.create'))->assertOk()->assertSee('hidden rounded-xl px-4 py-2.5', false);
});

it('offers a mobile menu with the hidden nav links', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<details', false)
        ->assertSee(route('about'), false)
        ->assertSee(route('contact.create'), false);

    $this->withSession(['locale' => 'ar'])->get(route('home'))
        ->assertOk()
        ->assertSee('القائمة', false);
});

it('serves home page aggregates from cache until flushed', function () {
    $category = ServiceCategory::factory()->create();
    Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Cached Service',
        'is_active' => true,
    ]);

    $this->get(route('home'))->assertOk()->assertSee('Cached Service', false);

    $extra = ServiceCategory::factory()->create(['name' => 'Extra Trade', 'slug' => 'extra-trade', 'is_active' => true]);
    Service::factory()->create([
        'service_category_id' => $extra->id,
        'name' => 'Fresh Service',
        'is_active' => true,
    ]);

    $this->get(route('home'))->assertOk()->assertDontSee('Fresh Service', false);

    Cache::flush();

    $this->get(route('home'))->assertOk()->assertSee('Fresh Service', false);
});
