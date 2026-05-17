<?php

namespace App\Models;

use Database\Factories\KpiTemplateItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiTemplateItem extends Model
{
    /** @use HasFactory<KpiTemplateItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'kpi_template_id',
        'sort_order',
        'name',
        'description',
        'weight',
        'target_description',
        'data_source',
        'is_required',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'is_required' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<KpiTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(KpiTemplate::class, 'kpi_template_id');
    }

    /**
     * @return HasMany<KpiScoreRule, $this>
     */
    public function scoreRules(): HasMany
    {
        return $this->hasMany(KpiScoreRule::class)->orderBy('score');
    }

    /**
     * @return HasMany<KpiAssessmentItem, $this>
     */
    public function assessmentItems(): HasMany
    {
        return $this->hasMany(KpiAssessmentItem::class, 'kpi_template_item_id');
    }
}
