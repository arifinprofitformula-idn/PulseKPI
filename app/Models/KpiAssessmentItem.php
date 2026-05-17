<?php

namespace App\Models;

use Database\Factories\KpiAssessmentItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiAssessmentItem extends Model
{
    /** @use HasFactory<KpiAssessmentItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'kpi_assessment_id',
        'kpi_template_item_id',
        'template_item_name',
        'template_item_description',
        'template_item_weight',
        'template_item_target_description',
        'template_item_data_source',
        'template_item_is_required',
        'actual_value',
        'score',
        'evidence_note',
        'evidence_file_path',
        'evidence_original_name',
        'evidence_mime_type',
        'evidence_size',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actual_value' => 'decimal:2',
            'template_item_weight' => 'decimal:2',
            'template_item_is_required' => 'boolean',
            'weighted_score' => 'decimal:2',
            'score' => 'integer',
            'evidence_size' => 'integer',
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
     * @return BelongsTo<KpiTemplateItem, $this>
     */
    public function templateItem(): BelongsTo
    {
        return $this->belongsTo(KpiTemplateItem::class, 'kpi_template_item_id');
    }
}
