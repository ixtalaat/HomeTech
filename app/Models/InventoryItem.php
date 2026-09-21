<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\InventoryItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    /** @use HasFactory<InventoryItemFactory> */
    use HasFactory, HasTranslations;

    /**
     * Translatable attributes stored in the translations table.
     *
     * @var list<string>
     */
    protected array $translatableFields = ['name', 'notes'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'sku',
        'unit',
        'current_stock',
        'low_stock_threshold',
        'unit_cost',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_stock' => 'integer',
            'low_stock_threshold' => 'integer',
            'unit_cost' => 'decimal:2',
        ];
    }

    /**
     * Get the movements recorded for the item.
     *
     * @return HasMany<InventoryMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Get the translations for the item.
     *
     * @return HasMany<InventoryItemTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(InventoryItemTranslation::class);
    }

    /**
     * Scope a query to items at or below their low-stock threshold.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('current_stock', '<=', 'low_stock_threshold');
    }

    /**
     * Scope a query to items with stock available.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('current_stock', '>', 0);
    }

    /**
     * Determine whether the item is at or below its low-stock threshold.
     */
    public function isLowOnStock(): bool
    {
        return $this->current_stock <= $this->low_stock_threshold;
    }

    /**
     * Recompute the stock from the movement ledger (audit check).
     */
    public function ledgerBalance(): int
    {
        return (int) $this->movements()->sum('quantity');
    }
}
