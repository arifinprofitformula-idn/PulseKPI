<?php

namespace App\Filament\Widgets;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Filament\Resources\KpiAssessments\KpiAssessmentResource;
use App\Filament\Resources\KpiAssignments\KpiAssignmentResource;
use App\Models\User;
use App\Services\Dashboard\KpiDashboardService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KpiRoleDashboardStatsWidget extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'KPI overview';

    protected ?string $description = 'Ringkasan KPI premium yang lebih mudah ditindaklanjuti, lengkap dengan status utama dan distribusi grade.';

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
        $assignmentUrl = KpiAssignmentResource::canViewAny() ? KpiAssignmentResource::getUrl('index') : null;
        $assessmentUrl = KpiAssessmentResource::canViewAny() ? KpiAssessmentResource::getUrl('index') : null;

        if ($user->hasRole(SystemRole::APPROVER->value)) {
            return [
                Stat::make('Reviewed', $stats['reviewed_assessments'])
                    ->description('Assessment telah direview')
                    ->descriptionIcon('heroicon-m-eye')
                    ->color('indigo')
                    ->url($assessmentUrl)
                    ->extraAttributes(['class' => 'pk-cc-stat']),
                Stat::make('Pending Approval', $stats['pending_approver_approval'])
                    ->description('Menunggu persetujuan Anda')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('warning')
                    ->url($assessmentUrl)
                    ->extraAttributes(['class' => 'pk-cc-stat']),
                Stat::make('Approved', $stats['approved_assessments'])
                    ->description('Assessment disetujui')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('success')
                    ->url($assessmentUrl)
                    ->extraAttributes(['class' => 'pk-cc-stat']),
                Stat::make('Locked', $stats['locked_assessments'])
                    ->description('Assessment terkunci')
                    ->descriptionIcon('heroicon-m-lock-closed')
                    ->color('slate')
                    ->url($assessmentUrl)
                    ->extraAttributes(['class' => 'pk-cc-stat']),
                Stat::make('Avg Final Score', number_format((float) $stats['average_final_score'], 2))
                    ->description('Rata-rata skor akhir')
                    ->descriptionIcon('heroicon-m-chart-bar')
                    ->color('primary')
                    ->url($assessmentUrl)
                    ->extraAttributes(['class' => 'pk-cc-stat']),
                Stat::make('Excellent', $stats['grade_distribution']['Excellent'])
                    ->description('Grade terbaik')
                    ->descriptionIcon('heroicon-m-trophy')
                    ->color('success')
                    ->extraAttributes(['class' => 'pk-cc-stat pk-cc-grade']),
                Stat::make('Good', $stats['grade_distribution']['Good'])
                    ->description('Performa solid')
                    ->descriptionIcon('heroicon-m-hand-thumb-up')
                    ->color('info')
                    ->extraAttributes(['class' => 'pk-cc-stat pk-cc-grade']),
                Stat::make('Fair', $stats['grade_distribution']['Fair'])
                    ->description('Masih perlu dorongan')
                    ->descriptionIcon('heroicon-m-adjustments-horizontal')
                    ->color('warning')
                    ->extraAttributes(['class' => 'pk-cc-stat pk-cc-grade']),
                Stat::make('Needs Improvement', $stats['grade_distribution']['Needs Improvement'])
                    ->description('Perlu perhatian')
                    ->descriptionIcon('heroicon-m-arrow-trending-down')
                    ->color('danger')
                    ->extraAttributes(['class' => 'pk-cc-stat pk-cc-grade']),
            ];
        }

        $assignmentLabel = $user->hasRole(SystemRole::MANAGER->value) ? 'Team KPI' : 'Total Assignments';

        return [
            Stat::make($assignmentLabel, $stats['total_assignments'])
                ->description('KPI assignment aktif')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary')
                ->url($assignmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Total Assessments', $stats['total_assessments'])
                ->description('Seluruh assessment')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Draft', $stats['draft_assessments'])
                ->description('Belum disubmit')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('gray')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Submitted', $stats['submitted_assessments'])
                ->description('Menunggu review')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('warning')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Reviewed', $stats['reviewed_assessments'])
                ->description('Sudah direview HRD')
                ->descriptionIcon('heroicon-m-eye')
                ->color('indigo')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Approved', $stats['approved_assessments'])
                ->description('Disetujui approver')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Locked', $stats['locked_assessments'])
                ->description('Terkunci & final')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('slate')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Rejected', $stats['rejected_assessments'])
                ->description('Ditolak, perlu revisi')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Pending HRD Review', $stats['pending_hrd_review'])
                ->description('Antrian review HRD')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Avg Final Score', number_format((float) $stats['average_final_score'], 2))
                ->description('Rata-rata skor akhir')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Excellent', $stats['grade_distribution']['Excellent'])
                ->description('Grade terbaik')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('success')
                ->extraAttributes(['class' => 'pk-cc-stat pk-cc-grade']),
            Stat::make('Good', $stats['grade_distribution']['Good'])
                ->description('Performa solid')
                ->descriptionIcon('heroicon-m-hand-thumb-up')
                ->color('info')
                ->extraAttributes(['class' => 'pk-cc-stat pk-cc-grade']),
            Stat::make('Fair', $stats['grade_distribution']['Fair'])
                ->description('Masih bisa ditingkatkan')
                ->descriptionIcon('heroicon-m-adjustments-horizontal')
                ->color('warning')
                ->extraAttributes(['class' => 'pk-cc-stat pk-cc-grade']),
            Stat::make('Needs Improvement', $stats['grade_distribution']['Needs Improvement'])
                ->description('Perlu follow-up')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->extraAttributes(['class' => 'pk-cc-stat pk-cc-grade']),
        ];
    }
}
