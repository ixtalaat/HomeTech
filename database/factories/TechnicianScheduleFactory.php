<?php

namespace Database\Factories;

use App\Models\Technician;
use App\Models\TechnicianSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TechnicianSchedule>
 */
class TechnicianScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'technician_id' => Technician::factory(),
            'day_of_week' => fake()->numberBetween(0, 6),
            'is_working' => true,
            'start_time' => '08:00',
            'end_time' => '17:00',
        ];
    }

    /**
     * Indicate a day off.
     */
    public function dayOff(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_working' => false,
            'start_time' => null,
            'end_time' => null,
        ]);
    }
}
