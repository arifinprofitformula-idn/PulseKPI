<?php

namespace App\Listeners;

use App\Events\KpiAssessmentLocked;
use Illuminate\Support\Facades\Log;

class SendKpiAssessmentLockedNotification
{
    public function handle(KpiAssessmentLocked $event): void
    {
        $assessment = $event->assessment->loadMissing(['assignment', 'employee', 'assessor']);

        Log::channel('stack')->info('kpi_assessment.notification.locked', [
            'assessment_id' => $assessment->getKey(),
            'assignment_id' => $assessment->kpi_assignment_id,
            'employee_id' => $assessment->employee_id,
            'employee_name' => $assessment->employee?->name,
            'assessor_id' => $assessment->assessor_id,
            'assessor_name' => $assessment->assessor?->name,
            'actor_id' => $event->actor?->getKey(),
            'actor_name' => $event->actor?->name,
            'status' => $assessment->status->value,
            'locked_at' => $assessment->locked_at?->toIso8601String(),
            'notes' => $event->notes,
        ]);
    }
}
