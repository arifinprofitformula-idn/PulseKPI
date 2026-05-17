<?php

namespace Database\Factories;

use App\Enums\KpiAssessmentStatus;
use App\Models\KpiAssessment;
use App\Models\KpiAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KpiAssessment>
 */
class KpiAssessmentFactory extends Factory
{
    protected $model = KpiAssessment::class;

    public function definition(): array
    {
        return [
            'kpi_assignment_id' => KpiAssignment::factory(),
            'employee_id' => User::factory(),
            'assessor_id' => User::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function submitted(): static
    {
        return $this
            ->afterMaking(function (KpiAssessment $assessment): void {
                $assessment->forceFill([
                    'status' => KpiAssessmentStatus::SUBMITTED,
                    'submitted_at' => now(),
                ]);
            })
            ->afterCreating(function (KpiAssessment $assessment): void {
                $assessment->forceFill([
                    'status' => KpiAssessmentStatus::SUBMITTED,
                    'submitted_at' => now(),
                ])->saveQuietly();
            });
    }

    public function rejected(): static
    {
        return $this
            ->afterMaking(function (KpiAssessment $assessment): void {
                $assessment->forceFill([
                    'status' => KpiAssessmentStatus::REJECTED,
                ]);
            })
            ->afterCreating(function (KpiAssessment $assessment): void {
                $assessment->forceFill([
                    'status' => KpiAssessmentStatus::REJECTED,
                ])->saveQuietly();
            });
    }
}
