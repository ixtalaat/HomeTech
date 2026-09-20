<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialUsage extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'material_usage';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'work_order_id',
        'inventory_item_id',
        'quantity',
        'unit_cost',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_cost' => 'decimal:2',
        ];
    }

    /**
     * Get the work order for the usage row.
     *
     * @return BelongsTo<WorkOrder, $this>
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * Get the inventory item used.
     *
     * @return BelongsTo<InventoryItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /**
     * Get the extended cost (quantity × frozen unit cost).
     */
    public function extendedCost(): float
    {
        return $this->quantity * (float) $this->unit_cost;
    }
}
