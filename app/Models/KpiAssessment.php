<?php

namespace App\Models;

use App\Enums\KpiAssessmentStatus;
use App\Enums\SystemRole;
use Database\Factories\KpiAssessmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property KpiAssessmentStatus $status
 * @property Carbon|null $submitted_at
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $rejected_at
 * @property Carbon|null $locked_at
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
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'locked_at' => 'datetime',
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
     * @return HasMany<KpiApproval, $this>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(KpiApproval::class)->latest('acted_at')->latest('id');
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

    /**
     * @param  Builder<KpiAssessment>  $query
     * @return Builder<KpiAssessment>
     */
    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        if ($user->hasRole(SystemRole::SUPER_ADMIN->value) || $user->hasRole(SystemRole::HRD->value)) {
            return $query;
        }

        if ($user->hasRole(SystemRole::APPROVER->value)) {
            return $query->whereIn('status', [
                KpiAssessmentStatus::REVIEWED->value,
                KpiAssessmentStatus::APPROVED->value,
                KpiAssessmentStatus::LOCKED->value,
            ]);
        }

        if ($user->isManager()) {
            return $query->whereHas(
                'employee',
                fn (Builder $builder) => $builder->where('supervisor_id', $user->getKey())
            );
        }

        return $query->where('employee_id', $user->getKey());
    }
}
