<?php

namespace App\Listeners;

use App\Events\KpiAssessmentRejected;
use Illuminate\Support\Facades\Log;

class SendKpiAssessmentRejectedNotification
{
    public function handle(KpiAssessmentRejected $event): void
    {
        $assessment = $event->assessment->loadMissing(['assignment', 'employee', 'assessor']);

        Log::channel('stack')->info('kpi_assessment.notification.rejected', [
            'assessment_id' => $assessment->getKey(),
            'assignment_id' => $assessment->kpi_assignment_id,
            'employee_id' => $assessment->employee_id,
            'employee_name' => $assessment->employee?->name,
            'assessor_id' => $assessment->assessor_id,
            'assessor_name' => $assessment->assessor?->name,
            'actor_id' => $event->actor?->getKey(),
            'actor_name' => $event->actor?->name,
            'status' => $assessment->status->value,
            'rejected_at' => $assessment->rejected_at?->toIso8601String(),
            'notes' => $event->notes,
        ]);
    }
}
