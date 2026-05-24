<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\HrdDashboard;
use App\Filament\Resources\KpiAssessments\KpiAssessmentResource;
use App\Filament\Resources\KpiAssignments\KpiAssignmentResource;
use App\Models\User;
use App\Services\Dashboard\HrdDashboardService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HrdOverviewStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Ringkasan operasional KPI';

    protected ?string $description = 'Ringkasan utama untuk assignment, workflow, performa akhir, dan distribusi grade dalam cakupan global.';

    public static function canView(): bool
    {
        return HrdDashboard::canAccess();
    }

    protected function getStats(): array
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $metrics = app(HrdDashboardService::class)->getCommandCenterMetrics($user);
        $assignmentUrl = KpiAssignmentResource::canViewAny() ? KpiAssignmentResource::getUrl('index') : null;
        $assessmentUrl = KpiAssessmentResource::canViewAny() ? KpiAssessmentResource::getUrl('index') : null;

        return [
            Stat::make('Total Assignments', number_format((int) $metrics['total_assignments']))
                ->description('Semua assignment KPI dalam cakupan dashboard')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('primary')
                ->url($assignmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Total Assessments', number_format((int) $metrics['total_assessments']))
                ->description('Seluruh assessment yang tercatat')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Draft', number_format((int) $metrics['draft_assessments']))
                ->description('Masih tersimpan sebagai draft')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('gray')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Submitted', number_format((int) $metrics['submitted_assessments']))
                ->description('Menunggu review HRD')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('warning')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Reviewed', number_format((int) $metrics['reviewed_assessments']))
                ->description('Sudah direview dan menunggu approver')
                ->descriptionIcon('heroicon-m-eye')
                ->color('indigo')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Approved', number_format((int) $metrics['approved_assessments']))
                ->description('Disetujui approver')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Locked', number_format((int) $metrics['locked_assessments']))
                ->description('Final dan tidak bisa diubah')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('slate')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Rejected', number_format((int) $metrics['rejected_assessments']))
                ->description('Perlu revisi sebelum lanjut')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Pending HRD Review', number_format((int) $metrics['pending_hrd_review']))
                ->description('Antrian operasional yang perlu ditindak')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Avg Final Score', number_format((float) $metrics['average_final_score'], 2))
                ->description('Rata-rata skor akhir')
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->color('primary')
                ->url($assessmentUrl)
                ->extraAttributes(['class' => 'pk-cc-stat']),
            Stat::make('Excellent', number_format((int) $metrics['grade_distribution']['Excellent']))
                ->description('Grade terbaik')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('success')
                ->extraAttributes(['class' => 'pk-cc-stat pk-cc-grade']),
            Stat::make('Good', number_format((int) $metrics['grade_distribution']['Good']))
                ->description('Performa solid')
                ->descriptionIcon('heroicon-m-hand-thumb-up')
                ->color('info')
                ->extraAttributes(['class' => 'pk-cc-stat pk-cc-grade']),
            Stat::make('Fair', number_format((int) $metrics['grade_distribution']['Fair']))
                ->description('Masih bisa ditingkatkan')
                ->descriptionIcon('heroicon-m-adjustments-horizontal')
                ->color('warning')
                ->extraAttributes(['class' => 'pk-cc-stat pk-cc-grade']),
            Stat::make('Needs Improvement', number_format((int) $metrics['grade_distribution']['Needs Improvement']))
                ->description('Perlu perhatian lebih')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->extraAttributes(['class' => 'pk-cc-stat pk-cc-grade']),
        ];
    }
}
