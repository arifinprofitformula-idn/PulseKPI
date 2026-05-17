<?php

namespace Database\Factories;

use App\Enums\KpiReportExportFormat;
use App\Enums\KpiReportExportStatus;
use App\Enums\KpiReportExportType;
use App\Models\KpiAssessment;
use App\Models\KpiReportExport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KpiReportExport>
 */
class KpiReportExportFactory extends Factory
{
    protected $model = KpiReportExport::class;

    public function definition(): array
    {
        return [
            'requested_by' => User::factory(),
            'kpi_assessment_id' => null,
            'type' => KpiReportExportType::ASSESSMENT_REPORT,
            'format' => KpiReportExportFormat::XLSX,
            'status' => KpiReportExportStatus::PENDING,
            'filters' => null,
            'file_path' => null,
            'file_name' => null,
            'disk' => 'local',
            'total_rows' => null,
            'started_at' => null,
            'finished_at' => null,
            'failed_at' => null,
            'error_message' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status' => KpiReportExportStatus::COMPLETED,
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
            'file_path' => 'exports/kpi-report.xlsx',
            'file_name' => 'kpi-report.xlsx',
            'total_rows' => 1,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => KpiReportExportStatus::FAILED,
            'started_at' => now()->subMinute(),
            'failed_at' => now(),
            'error_message' => 'Export failed.',
        ]);
    }

    public function forAssessment(KpiAssessment $assessment): static
    {
        return $this->state([
            'kpi_assessment_id' => $assessment->getKey(),
            'type' => KpiReportExportType::ASSESSMENT_DETAIL,
            'format' => KpiReportExportFormat::PDF,
        ]);
    }
}
