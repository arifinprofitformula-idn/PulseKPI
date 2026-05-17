<?php

namespace Database\Factories;

use App\Models\KpiAssessment;
use App\Models\KpiAttendanceAdjustment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KpiAttendanceAdjustment>
 */
class KpiAttendanceAdjustmentFactory extends Factory
{
    protected $model = KpiAttendanceAdjustment::class;

    public function definition(): array
    {
        return [
            'kpi_assessment_id' => KpiAssessment::factory(),
            'working_days' => 26,
            'sick_days' => 0,
            'permission_days' => 0,
            'absent_days' => 0,
            'leave_days' => 0,
            'deduction_score' => '0.00',
            'attendance_score' => '100.00',
        ];
    }
}
