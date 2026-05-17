<?php

namespace App\Listeners;

use App\Events\KpiAssigned;
use Illuminate\Support\Facades\Log;

class SendKpiAssignedNotification
{
    public function handle(KpiAssigned $event): void
    {
        $assignment = $event->assignment;

        $employee = $assignment->employee;
        $period = $assignment->period;
        $template = $assignment->template;

        Log::channel('stack')->info('kpi_assignment.notification.assigned', [
            'assignment_id' => $assignment->getKey(),
            'employee_id' => $assignment->employee_id,
            'employee_name' => $employee?->name,
            'period_id' => $assignment->kpi_period_id,
            'period_name' => $period?->name,
            'template_id' => $assignment->kpi_template_id,
            'template_name' => $template?->name,
            'assigned_at' => $assignment->assigned_at?->toIso8601String(),
        ]);
    }
}
