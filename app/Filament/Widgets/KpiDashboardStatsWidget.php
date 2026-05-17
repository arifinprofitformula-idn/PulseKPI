<?php

namespace App\Filament\Widgets;

use App\Models\Department;
use App\Models\Division;
use App\Models\KpiTemplate;
use App\Models\Position;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KpiDashboardStatsWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'HRD KPI Overview';

    protected ?string $description = 'Quick counts for organization setup and KPI template readiness.';

    protected function getStats(): array
    {
        $totalTemplates = KpiTemplate::query()->count();
        $draftTemplates = KpiTemplate::query()->whereNull('published_at')->count();
        $publishedTemplates = KpiTemplate::query()->whereNotNull('published_at')->count();
        $inactiveTemplates = KpiTemplate::query()->where('is_active', false)->count();

        return [
            Stat::make('Total Divisions', Division::query()->count())
                ->icon('heroicon-o-building-office')
                ->color('primary'),
            Stat::make('Total Departments', Department::query()->count())
                ->icon('heroicon-o-rectangle-stack')
                ->color('secondary'),
            Stat::make('Total Positions', Position::query()->count())
                ->icon('heroicon-o-briefcase')
                ->color('success'),
            Stat::make('Total KPI Templates', $totalTemplates)
                ->icon('heroicon-o-clipboard-document-list')
                ->color('warning'),
            Stat::make('Draft Templates', $draftTemplates)
                ->icon('heroicon-o-pencil-square')
                ->color('gray'),
            Stat::make('Published Templates', $publishedTemplates)
                ->icon('heroicon-o-check-badge')
                ->color('success'),
            Stat::make('Inactive Templates', $inactiveTemplates)
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }
}
