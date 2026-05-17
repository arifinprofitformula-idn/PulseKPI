<?php

namespace App\Actions\KpiAssessments;

use App\Enums\KpiAssessmentStatus;
use App\Enums\KpiAssignmentStatus;
use App\Models\KpiAssessment;
use App\Models\KpiAssignment;
use App\Models\User;
use App\Services\Audit\ActivityLogService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CreateKpiAssessmentAction
{
    public function __construct(
        private readonly CalculateKpiAssessmentScoreAction $calculateScore,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function execute(KpiAssignment|int $assignment, ?User $assessor = null): KpiAssessment
    {
        $resolvedAssignment = $assignment instanceof KpiAssignment
            ? $assignment->loadMissing(['template.items', 'employee'])
            : KpiAssignment::query()->with(['template.items', 'employee'])->findOrFail($assignment);

        $authorizer = $assessor ?? Auth::user();

        if ($authorizer !== null) {
            Gate::forUser($authorizer)->authorize('createForAssignment', [KpiAssessment::class, $resolvedAssignment]);
        }

        $this->ensureAssessmentCanBeCreated($resolvedAssignment, $authorizer);

        try {
            $assessment = DB::transaction(function () use ($resolvedAssignment, $authorizer): KpiAssessment {
                $assessment = KpiAssessment::query()->create([
                    'kpi_assignment_id' => $resolvedAssignment->getKey(),
                    'employee_id' => $resolvedAssignment->employee_id,
                    'assessor_id' => $authorizer?->getKey(),
                    'notes' => null,
                ]);

                $assessment->forceFill([
                    'status' => KpiAssessmentStatus::DRAFT,
                ])->saveQuietly();

                foreach ($resolvedAssignment->template->items as $templateItem) {
                    $assessment->items()->create([
                        'kpi_template_item_id' => $templateItem->getKey(),
                        'template_item_name' => $templateItem->name,
                        'template_item_description' => $templateItem->description,
                        'template_item_weight' => $templateItem->weight,
                        'template_item_target_description' => $templateItem->target_description,
                        'template_item_data_source' => $templateItem->data_source,
                        'template_item_is_required' => $templateItem->is_required,
                        'score' => null,
                        'weighted_score' => '0.00',
                    ]);
                }

                $assessment->attendanceAdjustment()->create([
                    'working_days' => 26,
                    'sick_days' => 0,
                    'permission_days' => 0,
                    'absent_days' => 0,
                    'leave_days' => 0,
                    'deduction_score' => '0.00',
                    'attendance_score' => '100.00',
                ]);

                $properties = [
                    'assessment_id' => $assessment->getKey(),
                    'assignment_id' => $resolvedAssignment->getKey(),
                    'employee_id' => $resolvedAssignment->employee_id,
                    'assessor_id' => $authorizer?->getKey(),
                    'status' => $assessment->status->value,
                ];

                $this->activityLogService->log(
                    'kpi_assessment.created',
                    $assessment,
                    $properties,
                    $authorizer
                );

                return $assessment;
            });
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'kpi_assignment_id' => 'An assessment already exists for the selected assignment.',
            ]);
        }

        return $this->calculateScore->execute($assessment);
    }

    private function ensureAssessmentCanBeCreated(KpiAssignment $assignment, ?User $assessor): void
    {
        $errors = [];

        if ($assignment->status !== KpiAssignmentStatus::ASSIGNED) {
            $errors['kpi_assignment_id'] = 'Only assigned KPI assignments can be assessed.';
        }

        if ($assignment->status === KpiAssignmentStatus::CANCELLED || $assignment->cancelled_at !== null) {
            $errors['kpi_assignment_id'] = 'Cancelled KPI assignments cannot be assessed.';
        }

        if ($assignment->assessment()->exists()) {
            $errors['kpi_assignment_id'] = 'An assessment already exists for the selected assignment.';
        }

        if ($assignment->employee === null) {
            $errors['employee_id'] = 'The selected assignment does not have a valid employee.';
        }

        if ($assessor !== null && $assignment->employee !== null && $assessor->is($assignment->employee)) {
            $errors['assessor_id'] = 'Self-assessment is not allowed.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
