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
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

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
        return 'Manager Dashboard';
    }

    public function getHeading(): string
    {
        return 'Manager Dashboard';
    }

    public function getHeader(): ?View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('filament.pages.manager-dashboard-header', [
            'user' => $user,
            'quickActions' => $this->getQuickActions(),
        ]);
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
     * @return list<array{label: string, url: string, style: string}>
     */
    protected function getQuickActions(): array
    {
        $actions = [];

        if (KpiAssignmentResource::canViewAny()) {
            $actions[] = [
                'label' => 'Open Team KPI',
                'url' => KpiAssignmentResource::getUrl('index'),
                'style' => 'secondary',
            ];
        }

        if (KpiAssessmentResource::canViewAny()) {
            $actions[] = [
                'label' => 'Open Assessment Queue',
                'url' => KpiAssessmentResource::getUrl('index'),
                'style' => 'primary',
            ];
        }

        if (KpiAssessmentReportResource::canViewAny()) {
            $actions[] = [
                'label' => 'Open Assessment History',
                'url' => KpiAssessmentReportResource::getUrl('index'),
                'style' => 'secondary',
            ];
        }

        return $actions;
    }
}
