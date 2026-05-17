<?php

namespace Database\Factories;

use App\Models\KpiTemplate;
use App\Models\KpiTemplateItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KpiTemplateItem>
 */
class KpiTemplateItemFactory extends Factory
{
    protected $model = KpiTemplateItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kpi_template_id' => KpiTemplate::factory(),
            'sort_order' => fake()->numberBetween(1, 10),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'weight' => '25.00',
            'target_description' => fake()->sentence(6),
            'data_source' => fake()->optional()->randomElement(['HRIS', 'Attendance', 'Manual Review']),
            'is_required' => true,
        ];
    }
}
