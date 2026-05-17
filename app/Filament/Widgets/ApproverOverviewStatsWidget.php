<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\ApproverDashboard;
use App\Models\User;
use App\Services\Dashboard\ApproverDashboardService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ApproverOverviewStatsWidget extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Approval snapshot';

    protected ?string $description = 'Approval-only metrics, scoped to the records your workflow can expose.';

    public static function canView(): bool
    {
        return ApproverDashboard::canAccess();
    }

    protected function getStats(): array
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $stats = app(ApproverDashboardService::class)->getSummaryMetrics($user);

        return [
            Stat::make('Pending Approval', $stats['pending_approval'])
                ->description('Reviewed assessments waiting on you')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('Approved', $stats['approved'])
                ->description('Currently approved in your scope')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Rejected Decisions', $stats['rejected'])
                ->description('Your rejection actions this cycle')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
            Stat::make('Locked', $stats['locked'])
                ->description('Finalized and locked assessments')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('slate'),
            Stat::make('Average Approved Score', number_format((float) $stats['average_approved_score'], 2))
                ->description('Approved records with score available')
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->color('primary'),
            Stat::make('Completed This Period', $stats['completed_this_period'])
                ->description('Approve, reject, and lock actions this month')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('indigo'),
        ];
    }
}
