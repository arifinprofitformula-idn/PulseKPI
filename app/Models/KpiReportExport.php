<?php

namespace App\Models;

use App\Enums\KpiReportExportFormat;
use App\Enums\KpiReportExportStatus;
use App\Enums\KpiReportExportType;
use Database\Factories\KpiReportExportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $requested_by
 * @property int|null $kpi_assessment_id
 * @property KpiReportExportType $type
 * @property KpiReportExportFormat $format
 * @property KpiReportExportStatus $status
 * @property array<string, mixed>|null $filters
 * @property string|null $file_path
 * @property string|null $file_name
 * @property string $disk
 * @property int|null $total_rows
 */
class KpiReportExport extends Model
{
    /** @use HasFactory<KpiReportExportFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'requested_by',
        'kpi_assessment_id',
        'type',
        'format',
        'status',
        'filters',
        'file_path',
        'file_name',
        'disk',
        'total_rows',
        'started_at',
        'finished_at',
        'failed_at',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => KpiReportExportType::class,
            'format' => KpiReportExportFormat::class,
            'status' => KpiReportExportStatus::class,
            'filters' => 'array',
            'total_rows' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<KpiAssessment, $this>
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(KpiAssessment::class, 'kpi_assessment_id');
    }
}
