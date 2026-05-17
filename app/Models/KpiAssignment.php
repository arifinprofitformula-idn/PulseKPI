<?php

namespace App\Models;

use App\Enums\KpiAssignmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property KpiAssignmentStatus $status
 * @property Carbon|null $assigned_at
 * @property Carbon|null $cancelled_at
 */
class KpiAssignment extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'kpi_period_id',
        'kpi_template_id',
        'employee_id',
        'assigned_by',
        'status',
        'assigned_at',
        'cancelled_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => KpiAssignmentStatus::class,
            'assigned_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<KpiPeriod, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(KpiPeriod::class, 'kpi_period_id');
    }

    /**
     * @return BelongsTo<KpiTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(KpiTemplate::class, 'kpi_template_id');
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
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * @return HasOne<KpiAssessment, $this>
     */
    public function assessment(): HasOne
    {
        return $this->hasOne(KpiAssessment::class, 'kpi_assignment_id');
    }
}
