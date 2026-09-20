<?php

use App\Enums\MovementType;
use App\Enums\UserRole;
use App\Exceptions\InsufficientStockException;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\MaterialUsage;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\InventoryService;

it('records usage with a linked consumption movement (BR-004)', function () {
    $service = app(InventoryService::class);
    $item = InventoryItem::factory()->create(['current_stock' => 25, 'unit_cost' => 150.00]);
    $workOrder = inProgressWorkOrder();

    $usage = $service->recordUsage($workOrder, $item, 1);

    expect($usage->quantity)->toBe(1)
        ->and((float) $usage->unit_cost)->toBe(150.00)
        ->and($item->refresh()->current_stock)->toBe(24);

    $movement = InventoryMovement::first();
    expect($movement->quantity)->toBe(-1)
        ->and($movement->type)->toBe(MovementType::Consumption)
        ->and($movement->work_order_id)->toBe($workOrder->id)
        ->and($movement->technician_id)->toBe($workOrder->technician_id);
});

it('rejects consumption beyond stock with zero side effects (BR-003)', function () {
    $service = app(InventoryService::class);
    $item = InventoryItem::factory()->create(['current_stock' => 2]);
    $workOrder = inProgressWorkOrder();

    expect(fn () => $service->recordUsage($workOrder, $item, 5))
        ->toThrow(InsufficientStockException::class);

    expect($item->refresh()->current_stock)->toBe(2)
        ->and(InventoryMovement::count())->toBe(0)
        ->and(MaterialUsage::count())->toBe(0);
});

it('reconciles the ledger with current stock', function () {
    $service = app(InventoryService::class);
    $item = InventoryItem::factory()->create(['current_stock' => 0]);
    $workOrder = inProgressWorkOrder();

    $service->purchase($item, 10);
    $service->recordUsage($workOrder, $item, 3);
    $service->returnStock($item, 1);

    expect($item->refresh()->current_stock)->toBe(8)
        ->and($item->ledgerBalance())->toBe(8);
});

it('reverses movements without editing history', function () {
    $service = app(InventoryService::class);
    $item = InventoryItem::factory()->create(['current_stock' => 0]);
    $workOrder = inProgressWorkOrder();

    $service->purchase($item, 10);
    $service->recordUsage($workOrder, $item, 4);

    $consumption = InventoryMovement::where('type', MovementType::Consumption)->first();
    $service->reverse($consumption);

    expect($item->refresh()->current_stock)->toBe(10)
        ->and(InventoryMovement::where('type', MovementType::Reversal)->count())->toBe(1)
        ->and($item->ledgerBalance())->toBe($item->current_stock);
});

it('flags low stock at and below the threshold', function () {
    $low = InventoryItem::factory()->create(['current_stock' => 5, 'low_stock_threshold' => 5]);
    $lower = InventoryItem::factory()->create(['current_stock' => 2, 'low_stock_threshold' => 5]);
    $ok = InventoryItem::factory()->create(['current_stock' => 6, 'low_stock_threshold' => 5]);

    $ids = InventoryItem::lowStock()->pluck('id')->all();

    expect($ids)->toContain($low->id, $lower->id)
        ->and($ids)->not->toContain($ok->id)
        ->and($low->isLowOnStock())->toBeTrue()
        ->and($ok->isLowOnStock())->toBeFalse();
});

it('lets technicians record usage on their open jobs', function () {
    $workOrder = inProgressWorkOrder();
    $techUser = $workOrder->technician->user;
    $item = InventoryItem::factory()->create(['current_stock' => 10, 'unit_cost' => 50.00]);

    $this->actingAs($techUser)->post(route('technician.jobs.materials', $workOrder), [
        'inventory_item_id' => $item->id,
        'quantity' => 2,
    ])->assertRedirect();

    expect($item->refresh()->current_stock)->toBe(8)
        ->and($workOrder->refresh()->materialsTotal())->toBe(100.00);
});

it('blocks usage beyond stock and on completed jobs', function () {
    $workOrder = inProgressWorkOrder();
    $techUser = $workOrder->technician->user;
    $item = InventoryItem::factory()->create(['current_stock' => 1]);

    $this->actingAs($techUser)->post(route('technician.jobs.materials', $workOrder), [
        'inventory_item_id' => $item->id,
        'quantity' => 5,
    ])->assertSessionHas('error');

    expect($item->refresh()->current_stock)->toBe(1);

    $completed = WorkOrder::factory()->completed()->create();
    $this->actingAs($completed->technician->user)->post(route('technician.jobs.materials', $completed), [
        'inventory_item_id' => $item->id,
        'quantity' => 1,
    ])->assertForbidden();
});

it('lets admins manage items with movements and audit logging', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->post(route('admin.inventory.store'), [
        'name' => 'Capacitor',
        'unit' => 'pcs',
        'current_stock' => 25,
        'low_stock_threshold' => 5,
        'unit_cost' => 150.00,
    ])->assertRedirect();

    $item = InventoryItem::where('name', 'Capacitor')->first();
    expect($item->current_stock)->toBe(25)
        ->and($item->movements()->where('type', MovementType::Purchase)->count())->toBe(1);

    $this->actingAs($admin)->patch(route('admin.inventory.adjust', $item), [
        'delta' => -5,
        'reason' => 'Damaged in storage.',
    ])->assertRedirect();

    expect($item->refresh()->current_stock)->toBe(20);

    $this->actingAs($admin)->patch(route('admin.inventory.adjust', $item), [
        'delta' => -100,
        'reason' => 'Too much.',
    ])->assertSessionHas('error');

    expect($item->refresh()->current_stock)->toBe(20);

    $this->actingAs($admin)->delete(route('admin.inventory.destroy', $item))->assertSessionHas('error');

    $fresh = InventoryItem::factory()->create(['current_stock' => 0]);
    $this->actingAs($admin)->delete(route('admin.inventory.destroy', $fresh))->assertRedirect();
    expect(InventoryItem::find($fresh->id))->toBeNull();
});

it('forbids non-staff from the inventory area', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $item = InventoryItem::factory()->create();

    $this->actingAs($customer)->get(route('admin.inventory.index'))->assertForbidden();
    $this->actingAs($customer)->post(route('admin.inventory.store'), [])->assertForbidden();
    $this->actingAs($customer)->patch(route('admin.inventory.adjust', $item), ['delta' => 1, 'reason' => 'x'])->assertForbidden();
});
