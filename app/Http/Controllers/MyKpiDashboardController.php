<?php

namespace App\Http\Controllers;

use App\Actions\Reports\BuildKpiAssessmentReportQuery;
use App\Models\KpiAssessment;
use App\Models\KpiAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MyKpiDashboardController extends Controller
{
    public function __invoke(Request $request, BuildKpiAssessmentReportQuery $buildReportQuery): View
    {
        abort_unless($request->user()->can('viewDashboard', KpiAssessment::class), 403);

        $latestAssignment = KpiAssignment::query()
            ->with(['period', 'template'])
            ->where('employee_id', $request->user()->getKey())
            ->latest('assigned_at')
            ->latest('id')
            ->first();

        $latestAssessment = $buildReportQuery
            ->execute($request->user())
            ->latest('submitted_at')
            ->latest('id')
            ->first();

        $recentAssessments = $buildReportQuery
            ->execute($request->user())
            ->latest('submitted_at')
            ->latest('id')
            ->limit(5)
            ->get();

        return view('my.kpi-dashboard', [
            'latestAssignment' => $latestAssignment,
            'latestAssessment' => $latestAssessment,
            'recentAssessments' => $recentAssessments,
        ]);
    }
}
