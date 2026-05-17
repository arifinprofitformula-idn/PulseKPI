<?php

namespace App\Listeners;

use App\Events\KpiAssessmentSubmitted;
use Illuminate\Support\Facades\Log;

class SendKpiAssessmentSubmittedNotification
{
    public function handle(KpiAssessmentSubmitted $event): void
    {
        $assessment = $event->assessment;
        $assignment = $assessment->assignment;
        $employee = $assessment->employee;
        $assessor = $assessment->assessor;

        Log::channel('stack')->info('kpi_assessment.notification.submitted', [
            'assessment_id' => $assessment->getKey(),
            'assignment_id' => $assessment->kpi_assignment_id,
            'employee_id' => $assessment->employee_id,
            'employee_name' => $employee?->name,
            'assessor_id' => $assessment->assessor_id,
            'assessor_name' => $assessor?->name,
            'period_id' => $assignment?->kpi_period_id,
            'template_id' => $assignment?->kpi_template_id,
            'final_score' => $assessment->final_score,
            'grade' => $assessment->grade,
            'submitted_at' => $assessment->submitted_at?->toIso8601String(),
        ]);
    }
}
