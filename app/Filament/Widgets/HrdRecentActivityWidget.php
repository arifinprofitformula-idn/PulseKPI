<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\HrdDashboard;
use App\Models\User;
use App\Services\Dashboard\HrdDashboardService;
use Filament\Widgets\Widget;

class HrdRecentActivityWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.hrd-recent-activity-widget';

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
                'activities' => [],
            ];
        }

        return [
            'activities' => app(HrdDashboardService::class)->getRecentActivity($user)->all(),
        ];
    }
}
