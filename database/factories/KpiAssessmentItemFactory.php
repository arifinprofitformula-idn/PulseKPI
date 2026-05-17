<?php

namespace Database\Factories;

use App\Models\KpiAssessment;
use App\Models\KpiAssessmentItem;
use App\Models\KpiTemplateItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KpiAssessmentItem>
 */
class KpiAssessmentItemFactory extends Factory
{
    protected $model = KpiAssessmentItem::class;

    public function definition(): array
    {
        $templateItem = KpiTemplateItem::factory()->create();

        return [
            'kpi_assessment_id' => KpiAssessment::factory(),
            'kpi_template_item_id' => $templateItem->getKey(),
            'template_item_name' => $templateItem->name,
            'template_item_description' => $templateItem->description,
            'template_item_weight' => $templateItem->weight,
            'template_item_target_description' => $templateItem->target_description,
            'template_item_data_source' => $templateItem->data_source,
            'template_item_is_required' => $templateItem->is_required,
            'actual_value' => null,
            'score' => null,
            'weighted_score' => '0.00',
            'evidence_note' => fake()->optional()->sentence(),
            'evidence_file_path' => null,
            'evidence_original_name' => null,
            'evidence_mime_type' => null,
            'evidence_size' => null,
        ];
    }
}
