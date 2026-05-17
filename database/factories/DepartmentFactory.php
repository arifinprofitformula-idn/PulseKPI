<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'division_id' => Division::factory(),
            'name' => fake()->unique()->jobTitle(),
            'code' => Str::upper(fake()->unique()->bothify('DEP-###')),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
