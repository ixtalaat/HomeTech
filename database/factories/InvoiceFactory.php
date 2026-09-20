<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'INV-'.fake()->unique()->numberBetween(10000, 99999),
            'maintenance_request_id' => MaintenanceRequest::factory(),
            'user_id' => User::factory(),
            'subtotal' => 800.00,
            'discount_amount' => 50.00,
            'total' => 750.00,
            'paid_amount' => 0,
            'status' => InvoiceStatus::Issued,
            'issued_at' => now(),
        ];
    }

    /**
     * Indicate that the invoice is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Draft,
            'issued_at' => null,
        ]);
    }

    /**
     * Indicate that the invoice is fully paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Paid,
            'paid_amount' => $attributes['total'] ?? 750.00,
        ]);
    }
}
