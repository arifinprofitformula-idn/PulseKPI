<?php

namespace App\Actions\Reports;

use App\Enums\KpiReportExportFormat;
use App\Enums\KpiReportExportStatus;
use App\Enums\KpiReportExportType;
use App\Jobs\ExportKpiAssessmentReportJob;
use App\Models\KpiAssessment;
use App\Models\KpiReportExport;
use App\Models\User;
use App\Services\Audit\ActivityLogService;
use Illuminate\Support\Facades\Gate;

class RequestKpiAssessmentReportExportAction
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(User $requester, array $filters = []): KpiReportExport
    {
        Gate::forUser($requester)->authorize('viewReports', KpiAssessment::class);

        $export = KpiReportExport::query()->create([
            'requested_by' => $requester->getKey(),
            'type' => KpiReportExportType::ASSESSMENT_REPORT,
            'format' => KpiReportExportFormat::XLSX,
            'status' => KpiReportExportStatus::PENDING,
            'filters' => $filters,
            'disk' => config('pulsekpi.exports.disk', 'local'),
        ]);

        $this->activityLogService->log('kpi_report_export.requested', $export, [
            'type' => $export->type->value,
            'format' => $export->format->value,
            'filters' => $filters,
        ], $requester);

        ExportKpiAssessmentReportJob::dispatch($export->getKey());

        return $export;
    }
}
