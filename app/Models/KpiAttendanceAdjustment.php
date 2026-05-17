<?php

namespace App\Models;

use Database\Factories\KpiAttendanceAdjustmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiAttendanceAdjustment extends Model
{
    /** @use HasFactory<KpiAttendanceAdjustmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'kpi_assessment_id',
        'working_days',
        'sick_days',
        'permission_days',
        'absent_days',
        'leave_days',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'working_days' => 'integer',
            'sick_days' => 'integer',
            'permission_days' => 'integer',
            'absent_days' => 'integer',
            'leave_days' => 'integer',
            'deduction_score' => 'decimal:2',
            'attendance_score' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<KpiAssessment, $this>
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(KpiAssessment::class, 'kpi_assessment_id');
    }
}
