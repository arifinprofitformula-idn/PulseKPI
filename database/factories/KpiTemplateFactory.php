<?php

namespace Database\Factories;

use App\Models\KpiTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<KpiTemplate>
 */
class KpiTemplateFactory extends Factory
{
    protected $model = KpiTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'code' => Str::upper(fake()->unique()->bothify('KPI-###??')),
            'year' => (int) fake()->numberBetween((int) now()->format('Y') - 1, (int) now()->format('Y') + 2),
            'division_id' => null,
            'department_id' => null,
            'position_id' => null,
            'revision' => '00',
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state([
            'published_at' => now(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }
}
