<?php

namespace App\Http\Controllers;

use App\Models\KpiAssessment;
use Illuminate\Contracts\View\View;

class MyKpiAssessmentController extends Controller
{
    public function show(KpiAssessment $kpiAssessment): View
    {
        return view('my.kpi-assessments.show', [
            'assessment' => $kpiAssessment->load([
                'assignment.period',
                'assignment.template',
                'employee',
                'assessor',
                'items',
                'attendanceAdjustment',
            ]),
        ]);
    }
}
