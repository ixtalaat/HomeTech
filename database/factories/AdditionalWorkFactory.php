<?php

namespace Database\Factories;

use App\Enums\AdditionalWorkStatus;
use App\Models\AdditionalWork;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdditionalWork>
 */
class AdditionalWorkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'work_order_id' => WorkOrder::factory(),
            'description' => fake()->sentence(),
            'cost' => fake()->randomFloat(2, 50, 1000),
            'status' => AdditionalWorkStatus::PendingApproval,
            'requested_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the work was approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AdditionalWorkStatus::Approved,
            'decided_by' => User::factory(),
            'decided_at' => now(),
        ]);
    }

    /**
     * Indicate that the work was rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AdditionalWorkStatus::Rejected,
            'decided_by' => User::factory(),
            'decided_at' => now(),
        ]);
    }
}
