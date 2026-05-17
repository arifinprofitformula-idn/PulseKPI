<?php

namespace App\Models;

use App\Enums\KpiAssessmentStatus;
use Database\Factories\KpiAssessmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property KpiAssessmentStatus $status
 * @property Carbon|null $submitted_at
 */
class KpiAssessment extends Model
{
    /** @use HasFactory<KpiAssessmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'kpi_assignment_id',
        'employee_id',
        'assessor_id',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => KpiAssessmentStatus::class,
            'kpi_score' => 'decimal:2',
            'attendance_score' => 'decimal:2',
            'attendance_deduction' => 'decimal:2',
            'final_score' => 'decimal:2',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<KpiAssignment, $this>
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(KpiAssignment::class, 'kpi_assignment_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }

    /**
     * @return HasMany<KpiAssessmentItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(KpiAssessmentItem::class)->orderBy('id');
    }

    /**
     * @return HasOne<KpiAttendanceAdjustment, $this>
     */
    public function attendanceAdjustment(): HasOne
    {
        return $this->hasOne(KpiAttendanceAdjustment::class);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [
            KpiAssessmentStatus::DRAFT,
            KpiAssessmentStatus::REJECTED,
        ], true);
    }
}
