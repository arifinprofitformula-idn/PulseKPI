<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\ManagerDashboard;
use App\Filament\Resources\KpiAssessmentReports\KpiAssessmentReportResource;
use App\Filament\Resources\KpiAssessments\KpiAssessmentResource;
use App\Filament\Resources\KpiAssignments\KpiAssignmentResource;
use App\Models\User;
use App\Services\Dashboard\ManagerDashboardService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ManagerOverviewStatsWidget extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected ?string $heading = 'KPI overview';

    protected ?string $description = 'Hanya menampilkan direct report Anda, lengkap dengan workload, progres assessment, dan distribusi grade.';

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
        $assignmentUrl = KpiAssignmentResource::canViewAny() ? KpiAssignmentResource::getUrl('index') : null;
        $assessmentUrl = KpiAssessmentResource::canViewAny() ? KpiAssessmentResource::getUrl('index') : null;
        $reportUrl = KpiAssessmentReportResource::canViewAny() ? KpiAssessmentReportResource::getUrl('index') : null;

        return [
            Stat::make('Direct Reports', $stats['team_members'])
                ->description('Karyawan dan supervisor langsung dalam scope')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->url($assignmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Total Assignments', $stats['total_assignments'])
                ->description('Seluruh KPI assignment tim Anda')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('info')
                ->url($assignmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Total Assessments', $stats['total_assessments'])
                ->description('Assessment yang sudah tercatat')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Pending Staff Assessments', $stats['assignments_to_assess'])
                ->description('Assigned KPI without assessment yet')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('warning')
                ->url($assignmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Draft Assessments', $stats['draft_assessments'])
                ->description('Still editable by manager')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('gray')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Submitted', $stats['submitted_assessments'])
                ->description('Visible as status, no longer editable')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('warning')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Rejected Revisions', $stats['rejected_assessments'])
                ->description('Need revision before resubmission')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('danger')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Approved or Locked', $stats['approved_or_locked'])
                ->description('Already past manager stage')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('indigo')
                ->url($reportUrl ?? $assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Average Team Score', number_format((float) $stats['average_team_score'], 2))
                ->description('Final score across scored assessments')
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->color('primary')
                ->url($reportUrl ?? $assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Excellent', $stats['excellent_grade'])
                ->description('Grade terbaik tim')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('success')
                ->extraAttributes(['class' => 'pk-cc-stat pk-cc-grade']),
            Stat::make('Good', $stats['good_grade'])
                ->description('Performa stabil')
                ->descriptionIcon('heroicon-m-hand-thumb-up')
                ->color('info')
                ->extraAttributes(['class' => 'pk-cc-stat pk-cc-grade']),
            Stat::make('Fair', $stats['fair_grade'])
                ->description('Butuh peningkatan')
                ->descriptionIcon('heroicon-m-adjustments-horizontal')
                ->color('warning')
                ->extraAttributes(['class' => 'pk-cc-stat pk-cc-grade']),
            Stat::make('Needs Improvement', $stats['needs_improvement_grade'])
                ->description('Perlu follow-up cepat')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->extraAttributes(['class' => 'pk-cc-stat pk-cc-grade']),
        ];
    }
}
