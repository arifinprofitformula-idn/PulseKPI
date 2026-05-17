<?php

namespace App\Models;

use App\Enums\KpiPeriodType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property KpiPeriodType $type
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 */
class KpiPeriod extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type',
        'month',
        'year',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => KpiPeriodType::class,
            'month' => 'integer',
            'year' => 'integer',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return HasMany<KpiAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(KpiAssignment::class, 'kpi_period_id');
    }
}
