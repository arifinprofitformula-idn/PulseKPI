<?php

namespace App\Http\Controllers;

use App\Models\KpiReportExport;
use App\Services\Audit\ActivityLogService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KpiReportExportDownloadController extends Controller
{
    public function __invoke(KpiReportExport $kpiReportExport, ActivityLogService $activityLogService): StreamedResponse
    {
        Gate::authorize('download', $kpiReportExport);

        if (! Storage::disk($kpiReportExport->disk)->exists((string) $kpiReportExport->file_path)) {
            abort(404);
        }

        $activityLogService->log('kpi_report_export.downloaded', $kpiReportExport, [
            'type' => $kpiReportExport->type->value,
            'format' => $kpiReportExport->format->value,
            'file_name' => $kpiReportExport->file_name,
        ], request()->user());

        return Storage::disk($kpiReportExport->disk)->download(
            $kpiReportExport->file_path,
            $kpiReportExport->file_name
        );
    }
}
