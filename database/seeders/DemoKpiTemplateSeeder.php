<?php

namespace Database\Seeders;

use App\Models\KpiScoreRule;
use App\Models\KpiTemplate;
use App\Models\KpiTemplateItem;
use App\Models\Position;
use Illuminate\Database\Seeder;

class DemoKpiTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $position = Position::query()
            ->where('code', 'OPS-MGR')
            ->first();

        if ($position === null) {
            return;
        }

        $template = KpiTemplate::query()->updateOrCreate(
            ['code' => 'LEADER-MARCOM-2026'],
            [
                'name' => 'Leader Marcom KPI 2026',
                'year' => 2026,
                'revision' => '00',
                'description' => 'Sample KPI template for Marketing Communications leadership.',
                'division_id' => $position->department->division_id,
                'department_id' => $position->department_id,
                'position_id' => $position->getKey(),
                'is_active' => true,
                'published_at' => null,
            ],
        );

        $items = [
            [
                'sort_order' => 1,
                'name' => 'Campaign Activation',
                'description' => 'Launch and execute integrated campaigns on schedule.',
                'weight' => '40.00',
                'target_description' => 'Deliver at least 3 campaigns with agreed KPIs.',
                'data_source' => 'Campaign Tracker',
                'is_required' => true,
                'scoreRules' => [
                    ['score' => 0, 'label' => 'Campaign not delivered', 'min_value' => '0.00', 'max_value' => '49.99', 'description' => 'Campaigns missed quality or timing targets.'],
                    ['score' => 1, 'label' => 'Campaign delivered with gaps', 'min_value' => '50.00', 'max_value' => '79.99', 'description' => 'Campaigns delivered but did not meet all targets.'],
                    ['score' => 2, 'label' => 'Campaign delivered successfully', 'min_value' => '80.00', 'max_value' => '100.00', 'description' => 'Campaigns met agreed metrics and timeline.'],
                ],
            ],
            [
                'sort_order' => 2,
                'name' => 'Digital Engagement',
                'description' => 'Grow marketing content interaction across owned channels.',
                'weight' => '35.00',
                'target_description' => 'Increase engagement by 15% vs baseline.',
                'data_source' => 'Analytics Dashboard',
                'is_required' => true,
                'scoreRules' => [
                    ['score' => 0, 'label' => 'Engagement fell short', 'min_value' => '0.00', 'max_value' => '49.99', 'description' => 'Engagement below target range.'],
                    ['score' => 1, 'label' => 'Engagement improved', 'min_value' => '50.00', 'max_value' => '79.99', 'description' => 'Engagement grew but did not fully meet target.'],
                    ['score' => 2, 'label' => 'Engagement target exceeded', 'min_value' => '80.00', 'max_value' => '100.00', 'description' => 'Engagement exceeded the target threshold.'],
                ],
            ],
            [
                'sort_order' => 3,
                'name' => 'Stakeholder Communication',
                'description' => 'Maintain proactive communication with stakeholders.',
                'weight' => '25.00',
                'target_description' => 'Share weekly updates and capture feedback.',
                'data_source' => 'Meeting Notes',
                'is_required' => false,
                'scoreRules' => [
                    ['score' => 0, 'label' => 'Communication inconsistent', 'min_value' => '0.00', 'max_value' => '49.99', 'description' => 'Updates were irregular or unclear.'],
                    ['score' => 1, 'label' => 'Communication adequate', 'min_value' => '50.00', 'max_value' => '79.99', 'description' => 'Communication happened but lacked consistency.'],
                    ['score' => 2, 'label' => 'Communication strong', 'min_value' => '80.00', 'max_value' => '100.00', 'description' => 'Updates were consistent and valuable.'],
                ],
            ],
        ];

        foreach ($items as $itemData) {
            $item = KpiTemplateItem::query()->updateOrCreate(
                [
                    'kpi_template_id' => $template->getKey(),
                    'name' => $itemData['name'],
                ],
                [
                    'sort_order' => $itemData['sort_order'],
                    'description' => $itemData['description'],
                    'weight' => $itemData['weight'],
                    'target_description' => $itemData['target_description'],
                    'data_source' => $itemData['data_source'],
                    'is_required' => $itemData['is_required'],
                ],
            );

            foreach ($itemData['scoreRules'] as $ruleData) {
                KpiScoreRule::query()->updateOrCreate(
                    [
                        'kpi_template_item_id' => $item->getKey(),
                        'score' => $ruleData['score'],
                    ],
                    [
                        'label' => $ruleData['label'],
                        'min_value' => $ruleData['min_value'],
                        'max_value' => $ruleData['max_value'],
                        'description' => $ruleData['description'],
                    ],
                );
            }
        }
    }
}
