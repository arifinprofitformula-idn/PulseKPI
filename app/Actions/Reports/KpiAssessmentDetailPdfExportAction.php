<?php

namespace App\Actions\Reports;

use App\Enums\KpiReportExportFormat;
use App\Enums\KpiReportExportStatus;
use App\Enums\KpiReportExportType;
use App\Models\KpiAssessment;
use App\Models\KpiReportExport;
use App\Models\User;
use App\Notifications\KpiReportExportCompletedNotification;
use App\Notifications\KpiReportExportFailedNotification;
use App\Services\Audit\ActivityLogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class KpiAssessmentDetailPdfExportAction
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function execute(KpiAssessment $assessment, User $requester): KpiReportExport
    {
        Gate::forUser($requester)->authorize('exportPdf', $assessment);

        $assessment->loadMissing([
            'assignment.period',
            'assignment.template',
            'employee.division',
            'employee.department',
            'employee.position',
            'assessor',
            'items',
            'attendanceAdjustment',
            'approvals.actor',
        ]);

        $export = KpiReportExport::query()->create([
            'requested_by' => $requester->getKey(),
            'kpi_assessment_id' => $assessment->getKey(),
            'type' => KpiReportExportType::ASSESSMENT_DETAIL,
            'format' => KpiReportExportFormat::PDF,
            'status' => KpiReportExportStatus::PENDING,
            'filters' => [
                'assessment_id' => $assessment->getKey(),
            ],
            'disk' => config('pulsekpi.exports.disk', 'local'),
        ]);

        $this->activityLogService->log('kpi_report_export.requested', $export, [
            'type' => $export->type->value,
            'format' => $export->format->value,
            'assessment_id' => $assessment->getKey(),
            'filters' => $export->filters,
        ], $requester);

        $this->activityLogService->log('kpi_assessment.pdf_export_requested', $assessment, [
            'export_id' => $export->getKey(),
            'format' => $export->format->value,
        ], $requester);

        try {
            $export->forceFill([
                'status' => KpiReportExportStatus::PROCESSING,
                'started_at' => now(),
                'failed_at' => null,
                'error_message' => null,
            ])->save();

            $this->activityLogService->log('kpi_report_export.processing', $export, [
                'type' => $export->type->value,
                'format' => $export->format->value,
                'assessment_id' => $assessment->getKey(),
            ], $requester);

            $fileName = sprintf(
                'kpi-assessment-%d-export-%d.pdf',
                $assessment->getKey(),
                $export->getKey()
            );
            $filePath = sprintf('exports/kpi-assessment-details/%s', $fileName);

            $pdf = Pdf::loadView('exports.kpi-assessment-detail', [
                'assessment' => $assessment,
            ]);

            Storage::disk($export->disk)->put($filePath, $pdf->output());

            $export->forceFill([
                'status' => KpiReportExportStatus::COMPLETED,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'total_rows' => 1,
                'finished_at' => now(),
            ])->save();

            $this->activityLogService->log('kpi_report_export.completed', $export, [
                'type' => $export->type->value,
                'format' => $export->format->value,
                'assessment_id' => $assessment->getKey(),
                'file_name' => $fileName,
                'total_rows' => 1,
            ], $requester);

            $this->activityLogService->log('kpi_assessment.pdf_export_completed', $assessment, [
                'export_id' => $export->getKey(),
                'file_name' => $fileName,
            ], $requester);

            Log::info('KPI assessment PDF export completed.', [
                'export_id' => $export->getKey(),
                'assessment_id' => $assessment->getKey(),
                'requested_by' => $requester->getKey(),
            ]);

            $requester->notify(new KpiReportExportCompletedNotification($export));

            return $export->fresh(['requester', 'assessment']);
        } catch (Throwable $throwable) {
            $safeError = str($throwable->getMessage())->limit(500)->toString();

            $export->forceFill([
                'status' => KpiReportExportStatus::FAILED,
                'failed_at' => now(),
                'error_message' => $safeError,
            ])->save();

            $this->activityLogService->log('kpi_report_export.failed', $export, [
                'type' => $export->type->value,
                'format' => $export->format->value,
                'assessment_id' => $assessment->getKey(),
                'error_message' => $safeError,
            ], $requester);

            Log::warning('KPI assessment PDF export failed.', [
                'export_id' => $export->getKey(),
                'assessment_id' => $assessment->getKey(),
                'requested_by' => $requester->getKey(),
                'error' => $safeError,
            ]);

            $requester->notify(new KpiReportExportFailedNotification($export));

            throw $throwable;
        }
    }
}
