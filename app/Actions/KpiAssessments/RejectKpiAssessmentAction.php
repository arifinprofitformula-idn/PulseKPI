<?php

namespace App\Actions\KpiAssessments;

use App\Enums\KpiApprovalAction;
use App\Enums\KpiAssessmentStatus;
use App\Events\KpiAssessmentRejected;
use App\Models\KpiAssessment;
use App\Models\User;
use App\Services\KpiAssessments\RecordKpiAssessmentTransitionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RejectKpiAssessmentAction
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
            Gate::forUser($authorizer)->authorize('reject', $resolvedAssessment);
        }

        if (! in_array($resolvedAssessment->status, [KpiAssessmentStatus::SUBMITTED, KpiAssessmentStatus::REVIEWED], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only submitted or reviewed assessments can be rejected.',
            ]);
        }

        $validated = Validator::make($data, [
            'notes' => ['required', 'string'],
        ])->validate();

        $fromStatus = $resolvedAssessment->status;
        $notes = $validated['notes'];

        DB::transaction(function () use ($resolvedAssessment, $authorizer, $fromStatus, $notes): void {
            $resolvedAssessment->forceFill([
                'status' => KpiAssessmentStatus::REJECTED,
                'rejected_at' => now(),
                'reviewed_at' => $fromStatus === KpiAssessmentStatus::REVIEWED
                    ? $resolvedAssessment->reviewed_at ?? now()
                    : null,
                'approved_at' => null,
                'locked_at' => null,
            ])->save();

            $this->transitionService->record(
                $resolvedAssessment,
                KpiApprovalAction::REJECTED,
                $fromStatus,
                KpiAssessmentStatus::REJECTED,
                $authorizer,
                $notes
            );
        });

        $resolvedAssessment->load(['assignment.period', 'assignment.template', 'employee', 'assessor', 'approvals.actor']);

        event(new KpiAssessmentRejected($resolvedAssessment, $authorizer, $notes));

        return $resolvedAssessment;
    }
}
