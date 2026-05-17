<?php

namespace Database\Factories;

use App\Enums\KpiPeriodType;
use App\Models\KpiPeriod;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KpiPeriod>
 */
class KpiPeriodFactory extends Factory
{
    protected $model = KpiPeriod::class;

    public function definition(): array
    {
        $type = fake()->randomElement([KpiPeriodType::MONTHLY, KpiPeriodType::YEARLY]);
        $year = (int) fake()->numberBetween((int) now()->format('Y'), (int) now()->format('Y') + 1);
        $month = $type === KpiPeriodType::MONTHLY ? fake()->numberBetween(1, 12) : null;
        $startsAt = $month !== null ? Carbon::create($year, $month, 1) : Carbon::create($year, 1, 1);
        $endsAt = $month !== null ? $startsAt->copy()->endOfMonth() : Carbon::create($year, 12, 31);

        return [
            'name' => $month !== null ? $startsAt->format('F Y') : (string) $year,
            'type' => $type->value,
            'month' => $month,
            'year' => $year,
            'starts_at' => $startsAt->toDateString(),
            'ends_at' => $endsAt->toDateString(),
            'is_active' => true,
        ];
    }

    public function yearly(): static
    {
        return $this->state(fn (): array => [
            'type' => KpiPeriodType::YEARLY->value,
            'month' => null,
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (): array => [
            'type' => KpiPeriodType::MONTHLY->value,
            'month' => fake()->numberBetween(1, 12),
        ]);
    }
}
