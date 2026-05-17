<?php

namespace App\Actions\KpiTemplates;

use App\Models\KpiTemplate;
use App\Models\KpiTemplateItem;
use App\Services\Audit\ActivityLogService;
use Illuminate\Support\Facades\DB;

class DuplicateKpiTemplateAction
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {}

    /**
     * @param  array{name?: string, code: string, year?: int, revision?: string, description?: ?string, division_id?: ?int, department_id?: ?int, position_id?: ?int}  $attributes
     */
    public function execute(KpiTemplate $template, array $attributes): KpiTemplate
    {
        return DB::transaction(function () use ($template, $attributes): KpiTemplate {
            $duplicate = KpiTemplate::query()->create([
                'name' => $attributes['name'] ?? $template->name,
                'code' => $attributes['code'],
                'year' => $attributes['year'] ?? $template->year,
                'division_id' => $attributes['division_id'] ?? $template->division_id,
                'department_id' => $attributes['department_id'] ?? $template->department_id,
                'position_id' => $attributes['position_id'] ?? $template->position_id,
                'revision' => $attributes['revision'] ?? $template->revision,
                'description' => $attributes['description'] ?? $template->description,
                'is_active' => false,
                'published_at' => null,
            ]);

            $template->loadMissing('items.scoreRules');

            foreach ($template->items as $item) {
                $duplicateItem = $duplicate->items()->create([
                    'sort_order' => $item->sort_order,
                    'name' => $item->name,
                    'description' => $item->description,
                    'weight' => $item->weight,
                    'target_description' => $item->target_description,
                    'data_source' => $item->data_source,
                    'is_required' => $item->is_required,
                ]);

                $this->duplicateScoreRules($item, $duplicateItem);
            }

            $duplicate = $duplicate->load('items.scoreRules');

            $this->activityLogService->log('kpi_template.duplicated', $duplicate, [
                'template_id' => $duplicate->getKey(),
                'source_template_id' => $template->getKey(),
                'new' => [
                    'code' => $duplicate->code,
                    'year' => $duplicate->year,
                    'revision' => $duplicate->revision,
                    'is_active' => $duplicate->is_active,
                    'published_at' => $duplicate->published_at,
                ],
            ]);

            return $duplicate;
        });
    }

    private function duplicateScoreRules(KpiTemplateItem $sourceItem, KpiTemplateItem $duplicateItem): void
    {
        foreach ($sourceItem->scoreRules as $scoreRule) {
            $duplicateItem->scoreRules()->create([
                'score' => $scoreRule->score,
                'label' => $scoreRule->label,
                'min_value' => $scoreRule->min_value,
                'max_value' => $scoreRule->max_value,
                'description' => $scoreRule->description,
            ]);
        }
    }
}
