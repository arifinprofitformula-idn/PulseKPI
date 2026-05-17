<?php

namespace App\Listeners;

use App\Events\KpiAssignmentCancelled;
use Illuminate\Support\Facades\Log;

class SendKpiAssignmentCancelledNotification
{
    public function handle(KpiAssignmentCancelled $event): void
    {
        $assignment = $event->assignment;

        $employee = $assignment->employee;
        $period = $assignment->period;
        $template = $assignment->template;

        Log::channel('stack')->info('kpi_assignment.notification.cancelled', [
            'assignment_id' => $assignment->getKey(),
            'employee_id' => $assignment->employee_id,
            'employee_name' => $employee?->name,
            'period_id' => $assignment->kpi_period_id,
            'period_name' => $period?->name,
            'template_id' => $assignment->kpi_template_id,
            'template_name' => $template?->name,
            'cancelled_at' => $assignment->cancelled_at?->toIso8601String(),
        ]);
    }
}
