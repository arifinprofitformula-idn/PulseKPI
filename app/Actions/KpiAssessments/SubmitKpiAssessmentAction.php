<?php

namespace App\Actions\KpiAssessments;

use App\Enums\KpiAssessmentStatus;
use App\Events\KpiAssessmentSubmitted;
use App\Models\KpiAssessment;
use App\Models\User;
use App\Services\Audit\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SubmitKpiAssessmentAction
{
    public function __construct(
        private readonly CalculateKpiAssessmentScoreAction $calculateScore,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function execute(KpiAssessment|int $assessment, ?User $actor = null): KpiAssessment
    {
        $resolvedAssessment = $assessment instanceof KpiAssessment
            ? $assessment->load(['items.templateItem', 'attendanceAdjustment', 'assignment'])
            : KpiAssessment::query()
                ->with(['items.templateItem', 'attendanceAdjustment', 'assignment'])
                ->findOrFail($assessment);

        $authorizer = $actor ?? Auth::user();

        if ($authorizer !== null) {
            Gate::forUser($authorizer)->authorize('submit', $resolvedAssessment);
        }

        if (! in_array($resolvedAssessment->status, [
            KpiAssessmentStatus::DRAFT,
            KpiAssessmentStatus::REJECTED,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only draft or rejected assessments can be submitted.',
            ]);
        }

        if ($resolvedAssessment->items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Assessment items are required before submission.',
            ]);
        }

        $missingRequiredScores = $resolvedAssessment->items
            ->filter(fn ($item): bool => $item->template_item_is_required && ($item->score === null))
            ->map(fn ($item): string => $item->template_item_name)
            ->values();

        if ($missingRequiredScores->isNotEmpty()) {
            throw ValidationException::withMessages([
                'items' => sprintf(
                    'All required KPI items must be scored before submission. Missing: %s.',
                    $missingRequiredScores->implode(', ')
                ),
            ]);
        }

        DB::transaction(function () use ($resolvedAssessment, $authorizer): void {
            $this->calculateScore->execute($resolvedAssessment);

            $resolvedAssessment->refresh();

            $fromStatus = $resolvedAssessment->status;

            $resolvedAssessment->forceFill([
                'status' => KpiAssessmentStatus::SUBMITTED,
                'submitted_at' => now(),
                'reviewed_at' => null,
                'approved_at' => null,
                'rejected_at' => null,
                'locked_at' => null,
            ])->save();

            $this->activityLogService->log(
                'kpi_assessment.submitted',
                $resolvedAssessment,
                [
                    'actor_id' => $authorizer?->getKey(),
                    'assessment_id' => $resolvedAssessment->getKey(),
                    'assignment_id' => $resolvedAssessment->kpi_assignment_id,
                    'employee_id' => $resolvedAssessment->employee_id,
                    'assessor_id' => $resolvedAssessment->assessor_id,
                    'from_status' => $fromStatus->value,
                    'to_status' => $resolvedAssessment->status->value,
                    'kpi_score' => $resolvedAssessment->kpi_score,
                    'attendance_deduction' => $resolvedAssessment->attendance_deduction,
                    'final_score' => $resolvedAssessment->final_score,
                    'grade' => $resolvedAssessment->grade,
                    'submitted_at' => $resolvedAssessment->submitted_at?->toIso8601String(),
                ],
                $authorizer
            );
        });

        $resolvedAssessment->load([
            'assignment.period',
            'assignment.template',
            'employee',
            'assessor',
            'items.templateItem',
            'attendanceAdjustment',
            'approvals.actor',
        ]);

        event(new KpiAssessmentSubmitted($resolvedAssessment));

        return $resolvedAssessment;
    }
}
