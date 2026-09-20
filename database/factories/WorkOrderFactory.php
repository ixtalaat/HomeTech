<?php

namespace Database\Factories;

use App\Enums\WorkOrderStatus;
use App\Models\Appointment;
use App\Models\MaintenanceRequest;
use App\Models\Technician;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkOrder>
 */
class WorkOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'maintenance_request_id' => MaintenanceRequest::factory(),
            'appointment_id' => Appointment::factory(),
            'technician_id' => Technician::factory(),
            'status' => WorkOrderStatus::InProgress,
            'started_at' => now(),
        ];
    }

    /**
     * Indicate that the work order is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkOrderStatus::Completed,
            'diagnosis' => fake()->paragraph(),
            'work_notes' => fake()->paragraph(),
            'completed_at' => now(),
        ]);
    }
}
