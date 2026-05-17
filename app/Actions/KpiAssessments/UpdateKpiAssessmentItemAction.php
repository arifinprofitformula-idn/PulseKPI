<?php

namespace App\Actions\KpiAssessments;

use App\Models\KpiAssessmentItem;
use App\Models\User;
use App\Services\Audit\ActivityLogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateKpiAssessmentItemAction
{
    public function __construct(
        private readonly CalculateKpiAssessmentScoreAction $calculateScore,
        private readonly ActivityLogService $activityLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(KpiAssessmentItem|int $item, array $data, ?User $actor = null): KpiAssessmentItem
    {
        $resolvedItem = $item instanceof KpiAssessmentItem
            ? $item->loadMissing(['assessment.assignment', 'assessment.employee', 'templateItem'])
            : KpiAssessmentItem::query()
                ->with(['assessment.assignment', 'assessment.employee', 'templateItem'])
                ->findOrFail($item);

        $authorizer = $actor ?? Auth::user();

        if ($authorizer !== null) {
            Gate::forUser($authorizer)->authorize('update', $resolvedItem->assessment);
        }

        if (! $resolvedItem->assessment->isEditable()) {
            throw ValidationException::withMessages([
                'assessment' => 'Only draft or rejected assessments can be edited.',
            ]);
        }

        $validated = Validator::make($data, [
            'item_id' => ['required', 'integer', Rule::in([$resolvedItem->getKey()])],
            'actual_value' => ['nullable', 'decimal:0,2'],
            'score' => ['required', 'integer', 'in:0,1,2'],
            'evidence_note' => ['nullable', 'string'],
            'evidence_file' => ['nullable', 'file', 'mimetypes:application/pdf,image/jpeg,image/png,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'max:'.(int) config('pulsekpi.assessments.max_evidence_size_kb', 5120)],
        ])->validate();

        DB::transaction(function () use ($resolvedItem, $validated, $authorizer): void {
            $oldEvidencePath = $resolvedItem->evidence_file_path;

            $attributes = [
                'actual_value' => $validated['actual_value'] ?? null,
                'score' => (int) $validated['score'],
                'evidence_note' => $validated['evidence_note'] ?? null,
            ];

            if (($validated['evidence_file'] ?? null) instanceof UploadedFile) {
                $file = $validated['evidence_file'];
                $disk = config('pulsekpi.assessments.evidence_disk', 'local');
                $storedPath = $file->store(
                    'kpi-assessment-evidence/'.$resolvedItem->assessment->getKey(),
                    $disk
                );

                $attributes['evidence_file_path'] = $storedPath;
                $attributes['evidence_original_name'] = $file->getClientOriginalName();
                $attributes['evidence_mime_type'] = $file->getMimeType();
                $attributes['evidence_size'] = $file->getSize();
            }

            $resolvedItem->fill($attributes);
            $resolvedItem->save();

            if (($validated['evidence_file'] ?? null) instanceof UploadedFile) {
                if (filled($oldEvidencePath)) {
                    Storage::disk(config('pulsekpi.assessments.evidence_disk', 'local'))->delete($oldEvidencePath);
                }

                $this->activityLogService->log(
                    'kpi_assessment.evidence_uploaded',
                    $resolvedItem->assessment,
                    [
                        'assessment_id' => $resolvedItem->assessment->getKey(),
                        'assignment_id' => $resolvedItem->assessment->kpi_assignment_id,
                        'employee_id' => $resolvedItem->assessment->employee_id,
                        'item_id' => $resolvedItem->getKey(),
                        'template_item_id' => $resolvedItem->kpi_template_item_id,
                        'file_name' => $resolvedItem->evidence_original_name,
                        'file_mime_type' => $resolvedItem->evidence_mime_type,
                        'file_size' => $resolvedItem->evidence_size,
                    ],
                    $authorizer
                );
            }

            $this->calculateScore->execute($resolvedItem->assessment);

            $this->activityLogService->log(
                'kpi_assessment_item.updated',
                $resolvedItem,
                [
                    'assessment_id' => $resolvedItem->assessment->getKey(),
                    'assignment_id' => $resolvedItem->assessment->kpi_assignment_id,
                    'employee_id' => $resolvedItem->assessment->employee_id,
                    'item_id' => $resolvedItem->getKey(),
                    'template_item_id' => $resolvedItem->kpi_template_item_id,
                    'score' => $resolvedItem->score,
                    'actual_value' => $resolvedItem->actual_value,
                    'weighted_score' => $resolvedItem->fresh()->weighted_score,
                    'has_evidence_file' => filled($resolvedItem->evidence_file_path),
                ],
                $authorizer
            );
        });

        return $resolvedItem->fresh(['assessment', 'templateItem']);
    }
}
