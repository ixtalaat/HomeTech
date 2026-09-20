<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Technician>
 */
class TechnicianFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create(['role' => UserRole::Technician])->id,
            'phone' => fake()->phoneNumber(),
            'emergency_contact' => fake()->optional()->phoneNumber(),
            'is_active' => true,
            'notes' => fake()->optional()->sentence(),
            'hired_at' => fake()->optional()->date(),
        ];
    }

    /**
     * Indicate that the technician is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
