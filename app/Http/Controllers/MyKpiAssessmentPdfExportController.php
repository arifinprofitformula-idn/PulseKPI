<?php

namespace App\Http\Controllers;

use App\Actions\Reports\KpiAssessmentDetailPdfExportAction;
use App\Models\KpiAssessment;
use Illuminate\Http\RedirectResponse;

class MyKpiAssessmentPdfExportController extends Controller
{
    public function __invoke(KpiAssessment $kpiAssessment, KpiAssessmentDetailPdfExportAction $exportAction): RedirectResponse
    {
        $export = $exportAction->execute($kpiAssessment, request()->user());

        return redirect()->route('kpi-report-exports.download', $export);
    }
}
