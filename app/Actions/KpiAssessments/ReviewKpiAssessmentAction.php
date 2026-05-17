<?php

namespace App\Actions\KpiAssessments;

use App\Enums\KpiApprovalAction;
use App\Enums\KpiAssessmentStatus;
use App\Events\KpiAssessmentReviewed;
use App\Models\KpiAssessment;
use App\Models\User;
use App\Services\KpiAssessments\RecordKpiAssessmentTransitionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReviewKpiAssessmentAction
{
    public function __construct(
        private readonly RecordKpiAssessmentTransitionService $transitionService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(KpiAssessment|int $assessment, array $data = [], ?User $actor = null): KpiAssessment
    {
        $resolvedAssessment = $assessment instanceof KpiAssessment
            ? $assessment->loadMissing(['assignment', 'employee', 'assessor'])
            : KpiAssessment::query()->with(['assignment', 'employee', 'assessor'])->findOrFail($assessment);

        $authorizer = $actor ?? Auth::user();

        if ($authorizer !== null) {
            Gate::forUser($authorizer)->authorize('review', $resolvedAssessment);
        }

        if ($resolvedAssessment->status !== KpiAssessmentStatus::SUBMITTED) {
            throw ValidationException::withMessages([
                'status' => 'Only submitted assessments can be reviewed.',
            ]);
        }

        $validated = Validator::make($data, [
            'notes' => ['nullable', 'string'],
        ])->validate();

        $fromStatus = $resolvedAssessment->status;
        $notes = $validated['notes'] ?? null;

        DB::transaction(function () use ($resolvedAssessment, $authorizer, $fromStatus, $notes): void {
            $resolvedAssessment->forceFill([
                'status' => KpiAssessmentStatus::REVIEWED,
                'reviewed_at' => now(),
                'rejected_at' => null,
            ])->save();

            $this->transitionService->record(
                $resolvedAssessment,
                KpiApprovalAction::REVIEWED,
                $fromStatus,
                KpiAssessmentStatus::REVIEWED,
                $authorizer,
                $notes
            );
        });

        $resolvedAssessment->load(['assignment.period', 'assignment.template', 'employee', 'assessor', 'approvals.actor']);

        event(new KpiAssessmentReviewed($resolvedAssessment, $authorizer, $notes));

        return $resolvedAssessment;
    }
}
