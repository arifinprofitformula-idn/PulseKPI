<?php

namespace App\Models;

use Database\Factories\KpiScoreRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiScoreRule extends Model
{
    /** @use HasFactory<KpiScoreRuleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'kpi_template_item_id',
        'score',
        'label',
        'min_value',
        'max_value',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'min_value' => 'decimal:2',
            'max_value' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<KpiTemplateItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(KpiTemplateItem::class, 'kpi_template_item_id');
    }
}
