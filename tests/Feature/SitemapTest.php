<?php

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::flush();
});

it('serves an XML sitemap with public pages and bookable services', function () {
    $category = ServiceCategory::factory()->create(['is_active' => true]);
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Sitemap Service',
        'slug' => 'sitemap-service',
        'is_active' => true,
    ]);
    Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Hidden Service',
        'slug' => 'hidden-service',
        'is_active' => false,
    ]);

    $response = $this->get(route('sitemap'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/xml; charset=UTF-8');

    $body = $response->getContent();
    expect($body)
        ->toContain(route('home'))
        ->toContain(route('services.show', 'sitemap-service'))
        ->not->toContain('hidden-service');
});
