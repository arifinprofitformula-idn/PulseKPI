<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KPI Assessment Detail</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1, h2, h3 { margin: 0 0 10px; }
        .section { margin-bottom: 20px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td, .grid th { border: 1px solid #d1d5db; padding: 6px 8px; vertical-align: top; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <div class="section">
        <h1>KPI Assessment Detail</h1>
        <p class="muted">Generated at {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <div class="section">
        <h2>Assessment Summary</h2>
        <table class="grid">
            <tr><th>Employee</th><td>{{ $assessment->employee?->name ?? '-' }}</td><th>Employee Code</th><td>{{ $assessment->employee?->employee_code ?? '-' }}</td></tr>
            <tr><th>Division</th><td>{{ $assessment->employee?->division?->name ?? '-' }}</td><th>Department</th><td>{{ $assessment->employee?->department?->name ?? '-' }}</td></tr>
            <tr><th>Position</th><td>{{ $assessment->employee?->position?->name ?? '-' }}</td><th>Period</th><td>{{ $assessment->assignment?->period?->name ?? '-' }}</td></tr>
            <tr><th>Template</th><td>{{ $assessment->assignment?->template?->name ?? '-' }}</td><th>Assessor</th><td>{{ $assessment->assessor?->name ?? '-' }}</td></tr>
            <tr><th>Status</th><td>{{ $assessment->status->label() }}</td><th>Grade</th><td>{{ $assessment->grade ?? '-' }}</td></tr>
            <tr><th>KPI Score</th><td>{{ $assessment->kpi_score }}</td><th>Attendance Deduction</th><td>{{ $assessment->attendance_deduction }}</td></tr>
            <tr><th>Attendance Score</th><td>{{ $assessment->attendance_score }}</td><th>Final Score</th><td>{{ $assessment->final_score }}</td></tr>
            <tr><th>Submitted At</th><td>{{ $assessment->submitted_at?->format('Y-m-d H:i:s') ?? '-' }}</td><th>Reviewed At</th><td>{{ $assessment->reviewed_at?->format('Y-m-d H:i:s') ?? '-' }}</td></tr>
            <tr><th>Approved At</th><td>{{ $assessment->approved_at?->format('Y-m-d H:i:s') ?? '-' }}</td><th>Locked At</th><td>{{ $assessment->locked_at?->format('Y-m-d H:i:s') ?? '-' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h2>KPI Components</h2>
        <table class="grid">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Description</th>
                    <th>Target</th>
                    <th>Data Source</th>
                    <th>Weight</th>
                    <th>Actual Value</th>
                    <th>Score</th>
                    <th>Weighted Score</th>
                    <th>Evidence Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($assessment->items as $item)
                    <tr>
                        <td>{{ $item->template_item_name }}</td>
                        <td>{{ $item->template_item_description ?? '-' }}</td>
                        <td>{{ $item->template_item_target_description }}</td>
                        <td>{{ $item->template_item_data_source ?? '-' }}</td>
                        <td>{{ $item->template_item_weight }}</td>
                        <td>{{ $item->actual_value ?? '-' }}</td>
                        <td>{{ $item->score ?? '-' }}</td>
                        <td>{{ $item->weighted_score }}</td>
                        <td>{{ $item->evidence_note ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Attendance Summary</h2>
        <table class="grid">
            <tr><th>Working Days</th><td>{{ $assessment->attendanceAdjustment?->working_days ?? '-' }}</td><th>Sick Days</th><td>{{ $assessment->attendanceAdjustment?->sick_days ?? '-' }}</td></tr>
            <tr><th>Permission Days</th><td>{{ $assessment->attendanceAdjustment?->permission_days ?? '-' }}</td><th>Absent Days</th><td>{{ $assessment->attendanceAdjustment?->absent_days ?? '-' }}</td></tr>
            <tr><th>Leave Days</th><td>{{ $assessment->attendanceAdjustment?->leave_days ?? '-' }}</td><th>Attendance Score</th><td>{{ $assessment->attendanceAdjustment?->attendance_score ?? $assessment->attendance_score }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h2>Approval History</h2>
        <table class="grid">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>From Status</th>
                    <th>To Status</th>
                    <th>Actor</th>
                    <th>Acted At</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($assessment->approvals as $approval)
                    <tr>
                        <td>{{ $approval->action->label() }}</td>
                        <td>{{ $approval->from_status ? str($approval->from_status)->headline()->toString() : '-' }}</td>
                        <td>{{ str($approval->to_status)->headline()->toString() }}</td>
                        <td>{{ $approval->actor?->name ?? 'System' }}</td>
                        <td>{{ $approval->acted_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                        <td>{{ $approval->notes ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No approval history available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
