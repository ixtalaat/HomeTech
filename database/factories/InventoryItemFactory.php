<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'unit' => 'pcs',
            'current_stock' => fake()->numberBetween(0, 50),
            'low_stock_threshold' => 5,
            'unit_cost' => fake()->randomFloat(2, 10, 500),
        ];
    }

    /**
     * Indicate that the item is low on stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stock' => 2,
            'low_stock_threshold' => 5,
        ]);
    }
}
