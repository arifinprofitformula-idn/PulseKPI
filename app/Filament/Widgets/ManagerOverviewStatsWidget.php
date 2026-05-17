<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\ManagerDashboard;
use App\Models\User;
use App\Services\Dashboard\ManagerDashboardService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ManagerOverviewStatsWidget extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Manager snapshot';

    protected ?string $description = 'Direct-report progress only, without HRD or approver actions mixed in.';

    public static function canView(): bool
    {
        return ManagerDashboard::canAccess();
    }

    protected function getStats(): array
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $stats = app(ManagerDashboardService::class)->getSummaryMetrics($user);

        return [
            Stat::make('Team Members', $stats['team_members'])
                ->description('Direct reports in scope')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make('Assignments to Assess', $stats['assignments_to_assess'])
                ->description('Assigned KPI without assessment yet')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('info'),
            Stat::make('Draft Assessments', $stats['draft_assessments'])
                ->description('Still editable by manager')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('gray'),
            Stat::make('Submitted', $stats['submitted_assessments'])
                ->description('Visible as status, no longer editable')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('warning'),
            Stat::make('Rejected Revisions', $stats['rejected_assessments'])
                ->description('Need revision before resubmission')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('danger'),
            Stat::make('Average Team Score', number_format((float) $stats['average_team_score'], 2))
                ->description('Final score across scored assessments')
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->color('success'),
            Stat::make('Approved or Locked', $stats['approved_or_locked'])
                ->description('Already past manager stage')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('indigo'),
        ];
    }
}
