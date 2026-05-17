<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\HrdDashboard;
use App\Models\User;
use App\Services\Dashboard\HrdDashboardService;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class HrdGradeDistributionChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    protected ?string $heading = 'Distribusi grade';

    protected ?string $description = 'Sebaran grade untuk assessment dalam cakupan dashboard ini.';

    protected string $color = 'success';

    protected function getType(): string
    {
        return 'doughnut';
    }

    public static function canView(): bool
    {
        return HrdDashboard::canAccess();
    }

    protected function getData(): array
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user instanceof User) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $distribution = app(HrdDashboardService::class)->getGradeDistribution($user);

        return [
            'datasets' => [
                [
                    'data' => $distribution['values'],
                    'backgroundColor' => [
                        '#0f9d8a',
                        '#2563eb',
                        '#f59e0b',
                        '#e11d48',
                        '#4f46e5',
                    ],
                ],
            ],
            'labels' => $distribution['labels'],
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'cutout' => '68%',
        ];
    }
}
