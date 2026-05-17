<?php

namespace App\Models;

use App\Enums\KpiApprovalAction;
use Database\Factories\KpiApprovalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiApproval extends Model
{
    /** @use HasFactory<KpiApprovalFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'kpi_assessment_id',
        'actor_id',
        'action',
        'from_status',
        'to_status',
        'notes',
        'acted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => KpiApprovalAction::class,
            'acted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<KpiAssessment, $this>
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(KpiAssessment::class, 'kpi_assessment_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
