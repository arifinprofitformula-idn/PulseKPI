<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\ApproverDashboard;
use App\Filament\Resources\KpiAssessmentReports\KpiAssessmentReportResource;
use App\Filament\Resources\KpiAssessments\KpiAssessmentResource;
use App\Models\User;
use App\Services\Dashboard\ApproverDashboardService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ApproverOverviewStatsWidget extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Approval command center';

    protected ?string $description = 'Metrik approval-only yang tetap mengikuti batas workflow record yang memang boleh Anda lihat.';

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
        $assessmentUrl = KpiAssessmentResource::canViewAny() ? KpiAssessmentResource::getUrl('index') : null;
        $reportUrl = KpiAssessmentReportResource::canViewAny() ? KpiAssessmentReportResource::getUrl('index') : null;

        return [
            Stat::make('Total Visible Assessments', $stats['total_assessments'])
                ->description('Reviewed, approved, dan locked saja')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Pending Approval', $stats['pending_approval'])
                ->description('Reviewed assessments waiting on you')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Approved', $stats['approved'])
                ->description('Currently approved in your scope')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Rejected Decisions', $stats['rejected'])
                ->description('Your rejection actions this cycle')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->url($reportUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Locked', $stats['locked'])
                ->description('Finalized and locked assessments')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('slate')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Average Approved Score', number_format((float) $stats['average_approved_score'], 2))
                ->description('Approved records with score available')
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->color('primary')
                ->url($reportUrl ?? $assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Completed This Period', $stats['completed_this_period'])
                ->description('Approve, reject, and lock actions this month')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('indigo')
                ->url($reportUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Excellent', $stats['excellent_grade'])
                ->description('Grade terbaik yang lolos workflow')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('success')
                ->extraAttributes(['class' => 'pk-cc-stat pk-cc-grade']),
            Stat::make('Good', $stats['good_grade'])
                ->description('Performa solid')
                ->descriptionIcon('heroicon-m-hand-thumb-up')
                ->color('info')
                ->extraAttributes(['class' => 'pk-cc-stat pk-cc-grade']),
            Stat::make('Fair', $stats['fair_grade'])
                ->description('Masih perlu dorongan')
                ->descriptionIcon('heroicon-m-adjustments-horizontal')
                ->color('warning')
                ->extraAttributes(['class' => 'pk-cc-stat pk-cc-grade']),
            Stat::make('Needs Improvement', $stats['needs_improvement_grade'])
                ->description('Butuh perhatian keputusan')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->extraAttributes(['class' => 'pk-cc-stat pk-cc-grade']),
        ];
    }
}
