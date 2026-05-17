<?php

namespace Database\Factories;

use App\Enums\KpiAssignmentStatus;
use App\Models\KpiAssignment;
use App\Models\KpiPeriod;
use App\Models\KpiTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KpiAssignment>
 */
class KpiAssignmentFactory extends Factory
{
    protected $model = KpiAssignment::class;

    public function definition(): array
    {
        return [
            'kpi_period_id' => KpiPeriod::factory(),
            'kpi_template_id' => KpiTemplate::factory()->published(),
            'employee_id' => User::factory(),
            'assigned_by' => User::factory(),
            'status' => KpiAssignmentStatus::ASSIGNED->value,
            'assigned_at' => now(),
            'cancelled_at' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function draft(): static
    {
        return $this->state([
            'status' => KpiAssignmentStatus::DRAFT->value,
            'assigned_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => KpiAssignmentStatus::CANCELLED->value,
            'cancelled_at' => now(),
        ]);
    }
}
