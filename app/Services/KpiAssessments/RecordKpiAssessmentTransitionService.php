<?php

namespace App\Services\KpiAssessments;

use App\Enums\KpiApprovalAction;
use App\Enums\KpiAssessmentStatus;
use App\Models\KpiAssessment;
use App\Models\User;
use App\Services\Audit\ActivityLogService;

class RecordKpiAssessmentTransitionService
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function record(
        KpiAssessment $assessment,
        KpiApprovalAction $action,
        KpiAssessmentStatus $fromStatus,
        KpiAssessmentStatus $toStatus,
        ?User $actor = null,
        ?string $notes = null,
    ): void {
        $assessment->approvals()->create([
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'from_status' => $fromStatus->value,
            'to_status' => $toStatus->value,
            'notes' => $notes,
            'acted_at' => now(),
        ]);

        $this->activityLogService->log(
            sprintf('kpi_assessment.%s', $action->value),
            $assessment,
            [
                'actor_id' => $actor?->getKey(),
                'assessment_id' => $assessment->getKey(),
                'assignment_id' => $assessment->kpi_assignment_id,
                'employee_id' => $assessment->employee_id,
                'assessor_id' => $assessment->assessor_id,
                'from_status' => $fromStatus->value,
                'to_status' => $toStatus->value,
                'notes' => $notes,
            ],
            $actor
        );
    }
}
