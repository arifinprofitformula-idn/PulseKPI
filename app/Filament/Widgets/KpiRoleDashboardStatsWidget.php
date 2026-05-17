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
    protected ?string $heading = 'KPI Overview';

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
                Stat::make('Reviewed', $stats['reviewed_assessments'])
                    ->description('Assessment telah direview')
                    ->descriptionIcon('heroicon-m-eye')
                    ->color('info'),
                Stat::make('Approved', $stats['approved_assessments'])
                    ->description('Assessment disetujui')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('success'),
                Stat::make('Locked', $stats['locked_assessments'])
                    ->description('Assessment terkunci')
                    ->descriptionIcon('heroicon-m-lock-closed')
                    ->color('gray'),
                Stat::make('Pending Approval', $stats['pending_approver_approval'])
                    ->description('Menunggu persetujuan Anda')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('warning'),
                Stat::make('Avg Final Score', number_format((float) $stats['average_final_score'], 2))
                    ->description('Rata-rata skor akhir')
                    ->descriptionIcon('heroicon-m-chart-bar')
                    ->color('primary'),
                Stat::make('Grade Distribution', $gradeSummary === '' ? '—' : $gradeSummary)
                    ->description('Distribusi grade keseluruhan')
                    ->descriptionIcon('heroicon-m-academic-cap')
                    ->color('secondary'),
            ];
        }

        $assignmentLabel = $user->hasRole(SystemRole::MANAGER->value) ? 'Team KPI' : 'Total Assignments';

        return [
            Stat::make($assignmentLabel, $stats['total_assignments'])
                ->description('KPI assignment aktif')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary'),
            Stat::make('Total Assessments', $stats['total_assessments'])
                ->description('Seluruh assessment')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('secondary'),
            Stat::make('Draft', $stats['draft_assessments'])
                ->description('Belum disubmit')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('gray'),
            Stat::make('Submitted', $stats['submitted_assessments'])
                ->description('Menunggu review')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('info'),
            Stat::make('Reviewed', $stats['reviewed_assessments'])
                ->description('Sudah direview HRD')
                ->descriptionIcon('heroicon-m-eye')
                ->color('warning'),
            Stat::make('Approved', $stats['approved_assessments'])
                ->description('Disetujui approver')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Locked', $stats['locked_assessments'])
                ->description('Terkunci & final')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('gray'),
            Stat::make('Rejected', $stats['rejected_assessments'])
                ->description('Ditolak, perlu revisi')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
            Stat::make('Pending HRD Review', $stats['pending_hrd_review'])
                ->description('Antrian review HRD')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('Avg Final Score', number_format((float) $stats['average_final_score'], 2))
                ->description('Rata-rata skor akhir')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),
            Stat::make('Grade Distribution', $gradeSummary === '' ? '—' : $gradeSummary)
                ->description('Distribusi grade')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('secondary'),
        ];
    }
}
