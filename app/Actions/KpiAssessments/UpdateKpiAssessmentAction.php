<?php

namespace App\Actions\KpiAssessments;

use App\Models\KpiAssessment;
use App\Models\User;
use App\Services\Audit\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateKpiAssessmentAction
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(KpiAssessment|int $assessment, array $data, ?User $actor = null): KpiAssessment
    {
        $resolvedAssessment = $assessment instanceof KpiAssessment
            ? $assessment->loadMissing('assignment')
            : KpiAssessment::query()->with('assignment')->findOrFail($assessment);

        $authorizer = $actor ?? Auth::user();

        if ($authorizer !== null) {
            Gate::forUser($authorizer)->authorize('update', $resolvedAssessment);
        }

        if (! $resolvedAssessment->isEditable()) {
            throw ValidationException::withMessages([
                'assessment' => 'Only draft or rejected assessments can be edited.',
            ]);
        }

        $validated = Validator::make($data, [
            'notes' => ['nullable', 'string'],
        ])->validate();

        $resolvedAssessment->fill([
            'notes' => $validated['notes'] ?? null,
        ]);
        $resolvedAssessment->save();

        $this->activityLogService->log(
            'kpi_assessment.updated',
            $resolvedAssessment,
            [
                'assessment_id' => $resolvedAssessment->getKey(),
                'assignment_id' => $resolvedAssessment->kpi_assignment_id,
                'employee_id' => $resolvedAssessment->employee_id,
                'notes' => $resolvedAssessment->notes,
            ],
            $authorizer
        );

        return $resolvedAssessment;
    }
}
