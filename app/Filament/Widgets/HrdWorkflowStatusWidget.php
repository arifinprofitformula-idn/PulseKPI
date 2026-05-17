<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\HrdDashboard;
use App\Models\User;
use App\Services\Dashboard\HrdDashboardService;
use Filament\Widgets\Widget;

class HrdWorkflowStatusWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.hrd-workflow-status-widget';

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return HrdDashboard::canAccess();
    }

    protected function getViewData(): array
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user instanceof User) {
            return [
                'statuses' => [],
            ];
        }

        $statuses = app(HrdDashboardService::class)->getWorkflowStatus($user);

        return [
            'statuses' => collect($statuses)
                ->map(fn (int $count, string $key): array => [
                    'key' => $key,
                    'label' => str($key)->replace('_', ' ')->title()->toString(),
                    'count' => $count,
                    'color' => HrdDashboardService::STATUS_COLORS[$key] ?? 'gray',
                ])
                ->values()
                ->all(),
        ];
    }
}
