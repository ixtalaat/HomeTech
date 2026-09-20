<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\Address;
use App\Models\MaintenanceRequest;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceRequest>
 */
class MaintenanceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'service_id' => Service::factory(),
            'address_id' => Address::factory(),
            'description' => fake()->paragraph(),
            'preferred_date' => fake()->dateTimeBetween('+1 day', '+14 days')->format('Y-m-d'),
            'preferred_time' => fake()->time('H:i'),
            'photos' => null,
            'status' => RequestStatus::PendingReview,
        ];
    }

    /**
     * Indicate that the request is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RequestStatus::Approved,
        ]);
    }

    /**
     * Indicate that the request is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RequestStatus::Rejected,
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
