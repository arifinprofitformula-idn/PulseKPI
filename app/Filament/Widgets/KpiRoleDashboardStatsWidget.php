<?php

namespace App\Filament\Widgets;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Models\User;
use App\Services\Dashboard\KpiDashboardService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KpiRoleDashboardStatsWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'KPI Dashboard';

    protected ?string $description = 'Role-scoped KPI progress and approval insights.';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->hasRole(SystemRole::SUPER_ADMIN->value)
                || $user->hasRole(SystemRole::HRD->value)
                || $user->hasRole(SystemRole::MANAGER->value)
                || $user->hasRole(SystemRole::APPROVER->value)
                || $user->can(SystemPermission::VIEW_REPORTS->value));
    }

    protected function getStats(): array
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $stats = app(KpiDashboardService::class)->getStats($user);
        $gradeSummary = collect($stats['grade_distribution'])
            ->map(fn (int $count, string $grade): string => "{$grade}: {$count}")
            ->implode(' | ');

        if ($user->hasRole(SystemRole::APPROVER->value)) {
            return [
                Stat::make('Reviewed Assessments', $stats['reviewed_assessments'])->color('info'),
                Stat::make('Approved Assessments', $stats['approved_assessments'])->color('primary'),
                Stat::make('Locked Assessments', $stats['locked_assessments'])->color('gray'),
                Stat::make('Pending Approver Approval', $stats['pending_approver_approval'])->color('warning'),
                Stat::make('Average Final Score', number_format((float) $stats['average_final_score'], 2))->color('success'),
                Stat::make('Grade Distribution', $gradeSummary === '' ? '-' : $gradeSummary)->color('secondary'),
            ];
        }

        $assignmentLabel = $user->hasRole(SystemRole::MANAGER->value) ? 'Team KPI' : 'Total Assignments';

        return [
            Stat::make($assignmentLabel, $stats['total_assignments'])->color('primary'),
            Stat::make('Total Assessments', $stats['total_assessments'])->color('secondary'),
            Stat::make('Draft Assessments', $stats['draft_assessments'])->color('warning'),
            Stat::make('Submitted Assessments', $stats['submitted_assessments'])->color('success'),
            Stat::make('Reviewed Assessments', $stats['reviewed_assessments'])->color('info'),
            Stat::make('Approved Assessments', $stats['approved_assessments'])->color('primary'),
            Stat::make('Locked Assessments', $stats['locked_assessments'])->color('gray'),
            Stat::make('Rejected Assessments', $stats['rejected_assessments'])->color('danger'),
            Stat::make('Pending HRD Review', $stats['pending_hrd_review'])->color('warning'),
            Stat::make('Average Final Score', number_format((float) $stats['average_final_score'], 2))->color('success'),
            Stat::make('Grade Distribution', $gradeSummary === '' ? '-' : $gradeSummary)->color('secondary'),
        ];
    }
}
