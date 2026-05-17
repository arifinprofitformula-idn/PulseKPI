<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\ManagerDashboard;
use App\Models\User;
use App\Services\Dashboard\ManagerDashboardService;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class ManagerPerformanceChartWidget extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'xl' => 2,
    ];

    protected ?string $heading = 'Team performance trend';

    protected ?string $description = 'Average final score by recent KPI period for your direct reports.';

    public static function canView(): bool
    {
        return ManagerDashboard::canAccess();
    }

    protected function getType(): string
    {
        return 'bar';
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

        $trend = app(ManagerDashboardService::class)->getPerformanceTrend($user);

        return [
            'datasets' => [
                [
                    'label' => 'Average score',
                    'data' => $trend['values'],
                    'backgroundColor' => ['rgba(15, 157, 138, 0.82)'],
                    'borderRadius' => 12,
                ],
            ],
            'labels' => $trend['labels'],
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'suggestedMax' => 100,
                ],
            ],
        ];
    }
}
