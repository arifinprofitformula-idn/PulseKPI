<?php

namespace App\Exports;

use App\Actions\Reports\BuildKpiAssessmentReportQuery;
use App\Models\KpiAssessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class KpiAssessmentReportExcelExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private readonly User $requester,
        private readonly array $filters = [],
    ) {}

    /**
     * @return Builder<KpiAssessment>
     */
    public function query(): Builder
    {
        return app(BuildKpiAssessmentReportQuery::class)
            ->execute($this->requester, $this->filters)
            ->orderBy('submitted_at')
            ->orderBy('id');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'Period',
            'Employee',
            'Division',
            'Department',
            'Position',
            'Assessor',
            'Template',
            'Status',
            'KPI Score',
            'Attendance Deduction',
            'Final Score',
            'Grade',
            'Submitted At',
            'Reviewed At',
            'Approved At',
            'Locked At',
        ];
    }

    /**
     * @return list<string>
     */
    public function map($row): array
    {
        /** @var KpiAssessment $row */
        $status = $row->status->label();

        return [
            (string) data_get($row, 'assignment.period.name', '-'),
            (string) data_get($row, 'employee.name', '-'),
            (string) data_get($row, 'employee.division.name', '-'),
            (string) data_get($row, 'employee.department.name', '-'),
            (string) data_get($row, 'employee.position.name', '-'),
            (string) data_get($row, 'assessor.name', '-'),
            (string) data_get($row, 'assignment.template.name', '-'),
            $status,
            (string) $row->kpi_score,
            (string) $row->attendance_deduction,
            (string) $row->final_score,
            $row->grade ?? '-',
            $row->submitted_at?->format('Y-m-d H:i:s') ?? '-',
            $row->reviewed_at?->format('Y-m-d H:i:s') ?? '-',
            $row->approved_at?->format('Y-m-d H:i:s') ?? '-',
            $row->locked_at?->format('Y-m-d H:i:s') ?? '-',
        ];
    }
}
