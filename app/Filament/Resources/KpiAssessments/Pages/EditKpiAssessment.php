<?php

namespace App\Filament\Resources\KpiAssessments\Pages;

use App\Actions\KpiAssessments\SubmitKpiAssessmentAction;
use App\Actions\KpiAssessments\UpdateKpiAssessmentAction;
use App\Actions\KpiAssessments\UpdateKpiAssessmentItemAction;
use App\Actions\KpiAssessments\UpdateKpiAttendanceAdjustmentAction;
use App\Filament\Resources\KpiAssessments\KpiAssessmentResource;
use App\Models\KpiAssessment;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditKpiAssessment extends EditRecord
{
    protected static string $resource = KpiAssessmentResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(
            static::getResource()::canEdit($this->getRecord()) || static::getResource()::canView($this->getRecord()),
            403
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var KpiAssessment $record */
        $record = $this->getRecord()->loadMissing([
            'assignment.period',
            'assignment.template',
            'employee',
            'assessor',
            'items.templateItem',
            'attendanceAdjustment',
        ]);

        return [
            ...$data,
            'employee_name' => $record->employee?->name,
            'assessor_name' => $record->assessor?->name,
            'period_name' => $record->assignment?->period?->name,
            'template_name' => $record->assignment?->template?->name,
            'status_label' => $record->status->label(),
            'kpi_score' => $record->kpi_score,
            'attendance_score' => $record->attendance_score,
            'attendance_deduction' => $record->attendance_deduction,
            'final_score' => $record->final_score,
            'grade' => $record->grade,
            'items' => $record->items->map(fn ($item): array => [
                'item_id' => $item->getKey(),
                'template_item_id' => $item->kpi_template_item_id,
                'item_name' => $item->template_item_name,
                'target_description' => $item->template_item_target_description,
                'data_source' => $item->template_item_data_source,
                'weight' => $item->template_item_weight,
                'description' => $item->template_item_description,
                'is_required' => $item->template_item_is_required,
                'actual_value' => $item->actual_value,
                'score' => $item->score,
                'weighted_score' => $item->weighted_score,
                'evidence_note' => $item->evidence_note,
                'current_evidence_name' => $item->evidence_original_name,
                'current_evidence_url' => filled($item->evidence_file_path)
                    ? route('kpi-assessment-items.evidence.download', ['item' => $item])
                    : null,
                'evidence_file' => null,
            ])->all(),
            'attendance' => [
                'working_days' => $record->attendanceAdjustment?->working_days ?? 26,
                'sick_days' => $record->attendanceAdjustment?->sick_days ?? 0,
                'permission_days' => $record->attendanceAdjustment?->permission_days ?? 0,
                'absent_days' => $record->attendanceAdjustment?->absent_days ?? 0,
                'leave_days' => $record->attendanceAdjustment?->leave_days ?? 0,
                'deduction_score' => $record->attendanceAdjustment?->deduction_score ?? '0.00',
                'attendance_score' => $record->attendanceAdjustment?->attendance_score ?? '100.00',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var KpiAssessment $record */
        app(UpdateKpiAssessmentAction::class)->execute($record, [
            'notes' => $data['notes'] ?? null,
        ], auth()->user());

        foreach ($data['items'] ?? [] as $itemData) {
            app(UpdateKpiAssessmentItemAction::class)->execute((int) $itemData['item_id'], $itemData, auth()->user());
        }

        app(UpdateKpiAttendanceAdjustmentAction::class)->execute(
            $record->attendanceAdjustment,
            $data['attendance'] ?? [],
            auth()->user()
        );

        return $record->fresh([
            'assignment.period',
            'assignment.template',
            'employee',
            'assessor',
            'items.templateItem',
            'attendanceAdjustment',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submitAssessment')
                ->label('Submit Assessment')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn (): bool => auth()->user()?->can('submit', $this->getRecord()) ?? false)
                ->requiresConfirmation()
                ->action(function (): void {
                    if (static::getResource()::canEdit($this->getRecord())) {
                        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                    }

                    app(SubmitKpiAssessmentAction::class)->execute($this->getRecord(), auth()->user());

                    $this->record->refresh();
                    $this->fillForm();

                    Notification::make()
                        ->success()
                        ->title('Assessment submitted successfully.')
                        ->send();
                }),
        ];
    }

    protected function getFormActions(): array
    {
        if (! static::getResource()::canEdit($this->getRecord())) {
            return [];
        }

        return parent::getFormActions();
    }
}
