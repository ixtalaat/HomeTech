<?php

use App\Enums\UserRole;
use App\Models\InventoryItem;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;

it('stores and falls back translations per model', function () {
    $category = ServiceCategory::factory()->create(['name' => 'Plumbing']);

    expect($category->display_name)->toBe('Plumbing');

    $category->saveTranslations(['ar' => ['name' => 'سباكة', 'description' => 'وصف']]);

    expect($category->refresh()->translate('ar')->name)->toBe('سباكة')
        ->and($category->translated('name', 'ar'))->toBe('سباكة')
        ->and($category->translated('name', 'en'))->toBe('Plumbing');

    app()->setLocale('ar');
    expect($category->refresh()->display_name)->toBe('سباكة');

    // Clearing reverts to the base fallback.
    $category->saveTranslations(['ar' => ['name' => '', 'description' => null]]);
    expect($category->refresh()->display_name)->toBe('Plumbing');
});

it('saves Arabic names through admin forms', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $category = ServiceCategory::factory()->create(['name' => 'Plumbing']);

    $this->actingAs($admin)->post(route('admin.services.store'), [
        'service_category_id' => $category->id,
        'name' => 'Faucet Repair',
        'name_ar' => 'إصلاح الحنفية',
        'base_price' => 200,
        'estimated_duration_minutes' => 60,
    ])->assertRedirect();

    $service = Service::where('name', 'Faucet Repair')->first();
    expect($service->translate('ar')->name)->toBe('إصلاح الحنفية');

    $item = InventoryItem::factory()->create(['name' => 'Capacitor']);
    $this->actingAs($admin)->put(route('admin.inventory.update', $item), [
        'name' => 'Capacitor',
        'unit' => 'pcs',
        'low_stock_threshold' => 5,
        'unit_cost' => 150,
        'name_ar' => 'مكثف',
    ])->assertRedirect();

    expect($item->refresh()->translate('ar')->name)->toBe('مكثف');
});

it('displays Arabic catalog content and finds it by Arabic search', function () {
    $category = ServiceCategory::factory()->create(['name' => 'Plumbing']);
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Faucet Repair',
        'is_active' => true,
    ]);
    $service->saveTranslations(['ar' => ['name' => 'إصلاح الحنفية']]);

    $this->withSession(['locale' => 'ar'])->get(route('services.index'))
        ->assertOk()
        ->assertSee('إصلاح الحنفية', false);

    $this->withSession(['locale' => 'ar'])
        ->get(route('services.index', ['search' => 'الحنفية']))
        ->assertOk()
        ->assertSee('إصلاح الحنفية', false);
});
