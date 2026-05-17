<?php

namespace Database\Factories;

use App\Enums\KpiApprovalAction;
use App\Models\KpiApproval;
use App\Models\KpiAssessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KpiApproval>
 */
class KpiApprovalFactory extends Factory
{
    protected $model = KpiApproval::class;

    public function definition(): array
    {
        return [
            'kpi_assessment_id' => KpiAssessment::factory(),
            'actor_id' => User::factory(),
            'action' => fake()->randomElement(KpiApprovalAction::cases()),
            'from_status' => fake()->optional()->randomElement(['draft', 'submitted', 'reviewed', 'approved', 'rejected']),
            'to_status' => fake()->randomElement(['submitted', 'reviewed', 'approved', 'rejected', 'locked']),
            'notes' => fake()->optional()->sentence(),
            'acted_at' => now(),
        ];
    }
}
