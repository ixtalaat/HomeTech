<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city(),
            'priority' => 0,
            'is_active' => true,
        ];
    }

    /**
     * Indicate an inactive branch.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Assign a branch-manager account to the branch.
     */
    public function withManager(?User $manager = null): static
    {
        return $this->afterCreating(function (Branch $branch) use ($manager): void {
            $manager ??= User::factory()->create(['role' => UserRole::Manager]);

            $branch->update(['manager_user_id' => $manager->id]);
        });
    }
}
