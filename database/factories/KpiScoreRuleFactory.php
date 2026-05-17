<?php

namespace Database\Factories;

use App\Models\KpiScoreRule;
use App\Models\KpiTemplateItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KpiScoreRule>
 */
class KpiScoreRuleFactory extends Factory
{
    protected $model = KpiScoreRule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $score = fake()->numberBetween(0, 2);

        return [
            'kpi_template_item_id' => KpiTemplateItem::factory(),
            'score' => $score,
            'label' => "Score {$score}",
            'min_value' => fake()->boolean() ? number_format((float) fake()->numberBetween(0, 50), 2, '.', '') : null,
            'max_value' => fake()->boolean() ? number_format((float) fake()->numberBetween(50, 100), 2, '.', '') : null,
            'description' => fake()->optional()->sentence(),
        ];
    }
}
