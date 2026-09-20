<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\MaterialUsage;
use App\Models\Technician;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Create an inventory item, recording the opening stock as a purchase movement.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createItem(array $attributes, ?User $actor = null): InventoryItem
    {
        return DB::transaction(function () use ($attributes, $actor): InventoryItem {
            $item = InventoryItem::create([
                'name' => $attributes['name'],
                'sku' => $attributes['sku'] ?? null,
                'unit' => $attributes['unit'],
                'current_stock' => 0,
                'low_stock_threshold' => $attributes['low_stock_threshold'],
                'unit_cost' => $attributes['unit_cost'],
                'notes' => $attributes['notes'] ?? null,
            ]);

            if (($attributes['current_stock'] ?? 0) > 0) {
                $this->applyMovement($item, (int) $attributes['current_stock'], MovementType::Purchase, null, null, $actor, 'Opening stock.');
                $item->refresh();
            }

            return $item;
        });
    }

    /**
     * Update an inventory item (stock changes only via movements, never directly).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateItem(InventoryItem $item, array $attributes): InventoryItem
    {
        $item->update([
            'name' => $attributes['name'],
            'sku' => $attributes['sku'] ?? null,
            'unit' => $attributes['unit'],
            'low_stock_threshold' => $attributes['low_stock_threshold'],
            'unit_cost' => $attributes['unit_cost'],
            'notes' => $attributes['notes'] ?? null,
        ]);

        return $item->refresh();
    }

    /**
     * Delete an item. Returns false when history references it.
     */
    public function deleteItem(InventoryItem $item): bool
    {
        if ($item->movements()->exists() || MaterialUsage::where('inventory_item_id', $item->id)->exists()) {
            return false;
        }

        $item->delete();

        return true;
    }

    /**
     * Record material usage for a work order: creates the usage row with a
     * frozen unit cost and decrements stock with a linked movement (BR-004).
     *
     * @throws InsufficientStockException
     */
    public function recordUsage(WorkOrder $workOrder, InventoryItem $item, int $quantity, ?User $actor = null): MaterialUsage
    {
        if ($quantity <= 0) {
            throw new InsufficientStockException('Usage quantity must be positive.');
        }

        return DB::transaction(function () use ($workOrder, $item, $quantity, $actor): MaterialUsage {
            $locked = $this->lock($item);

            $this->guardSufficientStock($locked, $quantity);

            $usage = MaterialUsage::create([
                'work_order_id' => $workOrder->id,
                'inventory_item_id' => $locked->id,
                'quantity' => $quantity,
                'unit_cost' => $locked->unit_cost,
            ]);

            $this->applyMovement(
                $locked,
                -$quantity,
                MovementType::Consumption,
                $workOrder,
                $workOrder->technician,
                $actor,
                "Used {$quantity} {$locked->unit} for work order #{$workOrder->id}."
            );

            return $usage;
        });
    }

    /**
     * Receive purchased stock.
     */
    public function purchase(InventoryItem $item, int $quantity, ?User $actor = null, ?string $notes = null): InventoryMovement
    {
        if ($quantity <= 0) {
            throw new InsufficientStockException('Purchase quantity must be positive.');
        }

        return DB::transaction(function () use ($item, $quantity, $actor, $notes): InventoryMovement {
            $locked = $this->lock($item);

            return $this->applyMovement($locked, $quantity, MovementType::Purchase, null, null, $actor, $notes);
        });
    }

    /**
     * Record a stock return (e.g. unused materials back to inventory).
     */
    public function returnStock(InventoryItem $item, int $quantity, ?User $actor = null, ?string $notes = null): InventoryMovement
    {
        if ($quantity <= 0) {
            throw new InsufficientStockException('Return quantity must be positive.');
        }

        return DB::transaction(function () use ($item, $quantity, $actor, $notes): InventoryMovement {
            $locked = $this->lock($item);

            return $this->applyMovement($locked, $quantity, MovementType::Return, null, null, $actor, $notes);
        });
    }

    /**
     * Apply a manual stock adjustment (positive or negative, never below zero).
     *
     * @throws InsufficientStockException
     */
    public function adjust(InventoryItem $item, int $delta, ?User $actor = null, ?string $reason = null): InventoryMovement
    {
        return DB::transaction(function () use ($item, $delta, $actor, $reason): InventoryMovement {
            $locked = $this->lock($item);

            if ($locked->current_stock + $delta < 0) {
                throw new InsufficientStockException(
                    "Adjustment of {$delta} would bring '{$locked->name}' below zero (current: {$locked->current_stock})."
                );
            }

            $movement = $this->applyMovement($locked, $delta, MovementType::Adjustment, null, null, $actor, $reason);

            AuditLog::record($actor, 'inventory.adjusted', $locked->refresh(), [
                'delta' => $delta,
                'stock' => $locked->current_stock,
            ], $reason);

            return $movement;
        });
    }

    /**
     * Reverse a prior movement with an offsetting entry (ledger stays append-only).
     */
    public function reverse(InventoryMovement $movement, ?User $actor = null, ?string $reason = null): InventoryMovement
    {
        return DB::transaction(function () use ($movement, $actor, $reason): InventoryMovement {
            $locked = $this->lock($movement->item);

            $reversal = -$movement->quantity;

            if ($locked->current_stock + $reversal < 0) {
                throw new InsufficientStockException(
                    "Reversing movement #{$movement->id} would bring '{$locked->name}' below zero."
                );
            }

            return $this->applyMovement(
                $locked,
                $reversal,
                MovementType::Reversal,
                null,
                null,
                $actor,
                $reason ?? "Reversal of movement #{$movement->id}."
            );
        });
    }

    /**
     * Lock the item row for a stock change.
     */
    private function lock(InventoryItem $item): InventoryItem
    {
        return InventoryItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
    }

    /**
     * Guard that sufficient stock exists (BR-003).
     *
     * @throws InsufficientStockException
     */
    private function guardSufficientStock(InventoryItem $item, int $quantity): void
    {
        if ($item->current_stock < $quantity) {
            throw new InsufficientStockException(
                "Insufficient stock for '{$item->name}': requested {$quantity}, available {$item->current_stock}."
            );
        }
    }

    /**
     * Apply a signed movement and update the stock counter.
     */
    private function applyMovement(
        InventoryItem $item,
        int $delta,
        MovementType $type,
        ?WorkOrder $workOrder,
        ?Technician $technician,
        ?User $actor,
        ?string $notes
    ): InventoryMovement {
        $item->update(['current_stock' => $item->current_stock + $delta]);

        return InventoryMovement::create([
            'inventory_item_id' => $item->id,
            'quantity' => $delta,
            'type' => $type,
            'work_order_id' => $workOrder?->id,
            'technician_id' => $technician?->id,
            'created_by' => $actor?->id,
            'notes' => $notes,
        ]);
    }
}
