<?php

namespace App\Filament\Pages;

use App\Enums\SystemRole;
use App\Filament\Resources\KpiAssessmentReports\KpiAssessmentReportResource;
use App\Filament\Resources\KpiAssessments\KpiAssessmentResource;
use App\Filament\Resources\KpiAssignments\KpiAssignmentResource;
use App\Filament\Widgets\HrdDivisionPerformanceWidget;
use App\Filament\Widgets\HrdFinalScoreTrendChart;
use App\Filament\Widgets\HrdGradeDistributionChart;
use App\Filament\Widgets\HrdLatestExportsWidget;
use App\Filament\Widgets\HrdOverviewStats;
use App\Filament\Widgets\HrdPendingReviewWidget;
use App\Filament\Widgets\HrdRecentActivityWidget;
use App\Filament\Widgets\HrdWorkflowStatusWidget;
use App\Models\User;
use App\Services\Dashboard\KpiDashboardService;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class KpiDashboard extends BaseDashboard
{
    protected static \UnitEnum|string|null $navigationGroup = 'Dashboard';

    protected static ?string $navigationLabel = 'KPI Dashboard';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static ?string $title = 'KPI Dashboard';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $target = match (true) {
            $user->hasRole(SystemRole::SUPERVISOR->value) => SupervisorDashboard::getUrl(),
            $user->hasRole(SystemRole::MANAGER->value) => ManagerDashboard::getUrl(),
            $user->hasRole(SystemRole::APPROVER->value) => ApproverDashboard::getUrl(),
            default => null,
        };

        if ($target !== null && request()->path() === 'admin') {
            $this->redirect($target, navigate: true);
        }
    }

    public function getHeading(): string
    {
        return 'KPI Command Center';
    }

    public function getSubheading(): ?string
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user instanceof User) {
            return 'Pantau progres KPI, tindak lanjuti assessment, dan jaga performa tim tetap terukur.';
        }

        $period = app(KpiDashboardService::class)->getActivePeriodLabel();
        $periodLabel = $period ? "Periode aktif {$period}." : 'Belum ada periode aktif yang berjalan.';

        return sprintf(
            'Selamat datang, %s. %s Pantau progres KPI, tindak lanjuti assessment, dan jaga performa tim tetap terukur.',
            $user->name,
            $periodLabel,
        );
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        $actions = [];

        if (KpiAssignmentResource::canViewAny()) {
            $actions[] = Action::make('openAssignments')
                ->label('Assignment KPI')
                ->url(KpiAssignmentResource::getUrl('index'))
                ->color('gray');
        }

        if (KpiAssessmentResource::canViewAny()) {
            $actions[] = Action::make('openAssessments')
                ->label('Assessment KPI')
                ->url(KpiAssessmentResource::getUrl('index'));
        }

        if (KpiAssessmentReportResource::canViewAny()) {
            $actions[] = Action::make('openReports')
                ->label('Laporan KPI')
                ->url(KpiAssessmentReportResource::getUrl('index'))
                ->color('success');
        }

        return $actions;
    }

    protected function getHeaderWidgets(): array
    {
        return HrdDashboard::canAccess()
            ? [
                HrdOverviewStats::class,
            ]
            : [];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    protected function getFooterWidgets(): array
    {
        return HrdDashboard::canAccess()
            ? [
                HrdFinalScoreTrendChart::class,
                HrdGradeDistributionChart::class,
                HrdWorkflowStatusWidget::class,
                HrdPendingReviewWidget::class,
                HrdRecentActivityWidget::class,
                HrdDivisionPerformanceWidget::class,
                HrdLatestExportsWidget::class,
            ]
            : [];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'xl' => 3,
        ];
    }
}
