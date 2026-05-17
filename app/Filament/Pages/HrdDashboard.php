<?php

namespace App\Filament\Pages;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Filament\Resources\KpiAssessments\KpiAssessmentResource;
use App\Filament\Resources\KpiAssignments\KpiAssignmentResource;
use App\Filament\Resources\KpiTemplates\KpiTemplateResource;
use App\Filament\Widgets\HrdDivisionPerformanceWidget;
use App\Filament\Widgets\HrdFinalScoreTrendChart;
use App\Filament\Widgets\HrdGradeDistributionChart;
use App\Filament\Widgets\HrdLatestExportsWidget;
use App\Filament\Widgets\HrdOverviewStats;
use App\Filament\Widgets\HrdPendingReviewWidget;
use App\Filament\Widgets\HrdRecentActivityWidget;
use App\Filament\Widgets\HrdWorkflowStatusWidget;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

class HrdDashboard extends Page
{
    protected static ?string $slug = 'hrd-dashboard';

    protected static \UnitEnum|string|null $navigationGroup = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard HRD';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static ?string $title = 'Dashboard HRD';

    protected static ?int $navigationSort = 0;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && (
                $user->hasRole(SystemRole::SUPER_ADMIN->value)
                || $user->hasRole(SystemRole::HRD->value)
                || $user->can(SystemPermission::VIEW_REPORTS->value)
            );
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->hasRole(SystemRole::SUPER_ADMIN->value) || $user->hasRole(SystemRole::HRD->value));
    }

    public function getHeader(): ?View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('filament.pages.hrd-dashboard-header', [
            'user' => $user,
            'quickActions' => $this->getQuickActions(),
        ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            HrdOverviewStats::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    protected function getFooterWidgets(): array
    {
        return [
            HrdFinalScoreTrendChart::class,
            HrdGradeDistributionChart::class,
            HrdWorkflowStatusWidget::class,
            HrdPendingReviewWidget::class,
            HrdRecentActivityWidget::class,
            HrdDivisionPerformanceWidget::class,
            HrdLatestExportsWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'xl' => 3,
        ];
    }

    /**
     * @return list<array{label: string, url: string, style: string}>
     */
    protected function getQuickActions(): array
    {
        $actions = [];

        if (KpiTemplateResource::canCreate()) {
            $actions[] = [
                'label' => 'Buat Template KPI',
                'url' => KpiTemplateResource::getUrl('create'),
                'style' => 'primary',
            ];
        }

        if (KpiAssignmentResource::canCreate()) {
            $actions[] = [
                'label' => 'Assign KPI',
                'url' => KpiAssignmentResource::getUrl('create'),
                'style' => 'secondary',
            ];
        }

        if (KpiAssessmentResource::canViewAny()) {
            $actions[] = [
                'label' => 'Review Assessment',
                'url' => KpiAssessmentResource::getUrl('index'),
                'style' => 'secondary',
            ];
        }

        return $actions;
    }
}
