<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\HrdDashboard;
use App\Models\User;
use App\Services\Dashboard\HrdDashboardService;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class HrdFinalScoreTrendChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'xl' => 2,
    ];

    protected ?string $heading = 'Trend final score';

    protected ?string $description = 'Rata-rata nilai akhir dari periode KPI terbaru yang tersedia.';

    protected function getType(): string
    {
        return 'line';
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

        $trend = app(HrdDashboardService::class)->getFinalScoreTrend($user);

        return [
            'datasets' => [
                [
                    'label' => 'Average Final Score',
                    'data' => $trend['values'],
                    'borderColor' => '#0f9d8a',
                    'backgroundColor' => 'rgba(15, 157, 138, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
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
