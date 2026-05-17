<?php

namespace App\Filament\Resources\KpiAssessments\Pages;

use App\Actions\KpiAssessments\ApproveKpiAssessmentAction;
use App\Actions\KpiAssessments\LockKpiAssessmentAction;
use App\Actions\KpiAssessments\RejectKpiAssessmentAction;
use App\Actions\KpiAssessments\ReviewKpiAssessmentAction;
use App\Actions\KpiAssessments\SubmitKpiAssessmentAction;
use App\Actions\KpiAssessments\UpdateKpiAssessmentAction;
use App\Actions\KpiAssessments\UpdateKpiAssessmentItemAction;
use App\Actions\KpiAssessments\UpdateKpiAttendanceAdjustmentAction;
use App\Actions\Reports\KpiAssessmentDetailPdfExportAction;
use App\Filament\Resources\KpiAssessments\KpiAssessmentResource;
use App\Models\KpiAssessment;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

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
            'approvals.actor',
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
            'submitted_at_label' => $record->submitted_at?->format('d M Y H:i'),
            'reviewed_at_label' => $record->reviewed_at?->format('d M Y H:i'),
            'approved_at_label' => $record->approved_at?->format('d M Y H:i'),
            'rejected_at_label' => $record->rejected_at?->format('d M Y H:i'),
            'locked_at_label' => $record->locked_at?->format('d M Y H:i'),
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
            'notes' => $record->notes,
            'approval_history' => $record->approvals->map(fn ($approval): array => [
                'action' => $approval->action->label(),
                'from_status' => $approval->from_status ? str($approval->from_status)->headline()->toString() : '-',
                'to_status' => str($approval->to_status)->headline()->toString(),
                'actor_name' => $approval->actor?->name ?? 'System',
                'acted_at' => $approval->acted_at?->format('d M Y H:i') ?? '-',
                'notes' => $approval->notes,
            ])->all(),
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
            'approvals.actor',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('warning')
                ->visible(fn (): bool => auth()->user()?->can('exportPdf', $this->getRecord()) ?? false)
                ->action(function (): RedirectResponse {
                    $export = app(KpiAssessmentDetailPdfExportAction::class)->execute(
                        $this->getRecord(),
                        auth()->user()
                    );

                    Notification::make()
                        ->success()
                        ->title('Assessment PDF export completed.')
                        ->send();

                    return redirect()->route('kpi-report-exports.download', $export);
                }),
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
            Action::make('reviewAssessment')
                ->label('Review')
                ->icon('heroicon-o-check-badge')
                ->color('info')
                ->visible(fn (): bool => auth()->user()?->can('review', $this->getRecord()) ?? false)
                ->schema([
                    Textarea::make('notes')
                        ->label('Review Notes')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    app(ReviewKpiAssessmentAction::class)->execute($this->getRecord(), $data, auth()->user());

                    $this->record->refresh();
                    $this->fillForm();

                    Notification::make()
                        ->success()
                        ->title('Assessment reviewed successfully.')
                        ->send();
                }),
            Action::make('approveAssessment')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->visible(fn (): bool => auth()->user()?->can('approve', $this->getRecord()) ?? false)
                ->schema([
                    Textarea::make('notes')
                        ->label('Approval Notes')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    app(ApproveKpiAssessmentAction::class)->execute($this->getRecord(), $data, auth()->user());

                    $this->record->refresh();
                    $this->fillForm();

                    Notification::make()
                        ->success()
                        ->title('Assessment approved successfully.')
                        ->send();
                }),
            Action::make('rejectAssessment')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => auth()->user()?->can('reject', $this->getRecord()) ?? false)
                ->schema([
                    Textarea::make('notes')
                        ->label('Rejection Notes')
                        ->rows(4)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    app(RejectKpiAssessmentAction::class)->execute($this->getRecord(), $data, auth()->user());

                    $this->record->refresh();
                    $this->fillForm();

                    Notification::make()
                        ->success()
                        ->title('Assessment rejected successfully.')
                        ->send();
                }),
            Action::make('lockAssessment')
                ->label('Lock')
                ->icon('heroicon-o-lock-closed')
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->can('lock', $this->getRecord()) ?? false)
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('notes')
                        ->label('Lock Notes')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    app(LockKpiAssessmentAction::class)->execute($this->getRecord(), $data, auth()->user());

                    $this->record->refresh();
                    $this->fillForm();

                    Notification::make()
                        ->success()
                        ->title('Assessment locked successfully.')
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
