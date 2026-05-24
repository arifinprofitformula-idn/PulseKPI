<?php

namespace App\Filament\Pages;

use App\Enums\SystemRole;
use App\Filament\Resources\KpiAssessmentReports\KpiAssessmentReportResource;
use App\Filament\Resources\KpiAssessments\KpiAssessmentResource;
use App\Filament\Resources\KpiAssignments\KpiAssignmentResource;
use App\Filament\Widgets\ManagerAssessmentQueueWidget;
use App\Filament\Widgets\ManagerOverviewStatsWidget;
use App\Filament\Widgets\ManagerPerformanceChartWidget;
use App\Filament\Widgets\ManagerTeamOverviewWidget;
use App\Models\User;
use App\Services\Dashboard\ManagerDashboardService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ManagerDashboard extends Page
{
    protected static ?string $slug = 'manager-dashboard';

    protected static \UnitEnum|string|null $navigationGroup = 'Dashboard';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 0;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->hasRole(SystemRole::MANAGER->value) || $user->hasRole(SystemRole::SUPER_ADMIN->value));
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasRole(SystemRole::MANAGER->value);
    }

    public static function getNavigationLabel(): string
    {
        return 'Manager Dashboard';
    }

    public function getTitle(): string
    {
        return 'KPI Command Center';
    }

    public function getHeading(): string
    {
        return 'KPI Command Center';
    }

    public function getSubheading(): ?string
    {
        /** @var User|null $user */
        $user = auth()->user();
        $period = app(ManagerDashboardService::class)->getActivePeriodLabel();

        if (! $user instanceof User) {
            return 'Pantau progres KPI, tindak lanjuti assessment, dan jaga performa tim tetap terukur.';
        }

        $periodLabel = $period ? "Periode aktif {$period}." : 'Belum ada periode aktif yang berjalan.';

        return sprintf(
            'Selamat datang, %s. Manager dashboard ini hanya menampilkan direct report Anda. %s Pantau progres KPI, tindak lanjuti assessment, dan jaga performa tim tetap terukur.',
            $user->name,
            $periodLabel,
        );
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ManagerOverviewStatsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    protected function getFooterWidgets(): array
    {
        return [
            ManagerTeamOverviewWidget::class,
            ManagerAssessmentQueueWidget::class,
            ManagerPerformanceChartWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'xl' => 2,
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        $actions = [];

        if (KpiAssignmentResource::canViewAny()) {
            $actions[] = Action::make('openTeamKpi')
                ->label('Open Team KPI')
                ->url(KpiAssignmentResource::getUrl('index'))
                ->color('gray');
        }

        if (KpiAssessmentResource::canViewAny()) {
            $actions[] = Action::make('openAssessmentQueue')
                ->label('Open Assessment Queue')
                ->url(KpiAssessmentResource::getUrl('index'));
        }

        if (KpiAssessmentReportResource::canViewAny()) {
            $actions[] = Action::make('openAssessmentHistory')
                ->label('Open Assessment History')
                ->url(KpiAssessmentReportResource::getUrl('index'))
                ->color('success');
        }

        return $actions;
    }
}
