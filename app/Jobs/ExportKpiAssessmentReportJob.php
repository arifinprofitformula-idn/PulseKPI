<?php

namespace App\Jobs;

use App\Actions\Reports\BuildKpiAssessmentReportQuery;
use App\Enums\KpiReportExportStatus;
use App\Exports\KpiAssessmentReportExcelExport;
use App\Models\KpiReportExport;
use App\Notifications\KpiReportExportCompletedNotification;
use App\Notifications\KpiReportExportFailedNotification;
use App\Services\Audit\ActivityLogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ExportKpiAssessmentReportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $exportId,
    ) {}

    public function handle(
        ActivityLogService $activityLogService,
        BuildKpiAssessmentReportQuery $buildReportQuery,
    ): void {
        $export = KpiReportExport::query()->with('requester')->findOrFail($this->exportId);
        $requester = $export->requester;

        if ($requester === null) {
            $export->forceFill([
                'status' => KpiReportExportStatus::FAILED,
                'failed_at' => now(),
                'error_message' => 'Requester no longer exists.',
            ])->save();

            return;
        }

        $export->forceFill([
            'status' => KpiReportExportStatus::PROCESSING,
            'started_at' => now(),
            'failed_at' => null,
            'error_message' => null,
        ])->save();

        $activityLogService->log('kpi_report_export.processing', $export, [
            'type' => $export->type->value,
            'format' => $export->format->value,
            'filters' => $export->filters,
        ], $requester);

        try {
            $filters = $export->filters ?? [];
            $totalRows = $buildReportQuery->execute($requester, $filters)->count();
            $fileName = sprintf('kpi-assessment-report-export-%d.xlsx', $export->getKey());
            $filePath = sprintf('exports/kpi-assessment-reports/%s', $fileName);

            Excel::store(
                new KpiAssessmentReportExcelExport($requester, $filters),
                $filePath,
                $export->disk
            );

            $export->forceFill([
                'status' => KpiReportExportStatus::COMPLETED,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'total_rows' => $totalRows,
                'finished_at' => now(),
            ])->save();

            $activityLogService->log('kpi_report_export.completed', $export, [
                'type' => $export->type->value,
                'format' => $export->format->value,
                'filters' => $filters,
                'file_name' => $fileName,
                'total_rows' => $totalRows,
            ], $requester);

            Log::info('KPI assessment report export completed.', [
                'export_id' => $export->getKey(),
                'requested_by' => $requester->getKey(),
                'total_rows' => $totalRows,
            ]);

            $requester->notify(new KpiReportExportCompletedNotification($export));
        } catch (Throwable $throwable) {
            $safeError = str($throwable->getMessage())->limit(500)->toString();

            $export->forceFill([
                'status' => KpiReportExportStatus::FAILED,
                'failed_at' => now(),
                'error_message' => $safeError,
            ])->save();

            $activityLogService->log('kpi_report_export.failed', $export, [
                'type' => $export->type->value,
                'format' => $export->format->value,
                'filters' => $export->filters,
                'error_message' => $safeError,
            ], $requester);

            Log::warning('KPI assessment report export failed.', [
                'export_id' => $export->getKey(),
                'requested_by' => $requester->getKey(),
                'error' => $safeError,
            ]);

            $requester->notify(new KpiReportExportFailedNotification($export));

            throw $throwable;
        }
    }
}
