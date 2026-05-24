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
use App\Services\Dashboard\HrdDashboardService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

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
        return false;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->redirect(KpiDashboard::getUrl(), navigate: true);
    }

    public function getHeading(): string
    {
        return 'KPI Command Center';
    }

    public function getSubheading(): ?string
    {
        /** @var User|null $user */
        $user = auth()->user();
        $period = app(HrdDashboardService::class)->getActivePeriodLabel();

        if (! $user instanceof User) {
            return 'Pantau progres KPI, tindak lanjuti assessment, dan jaga performa tim tetap terukur.';
        }

        $roleLabel = $user->hasRole(SystemRole::SUPER_ADMIN->value) ? 'Super Admin' : 'HRD';
        $periodLabel = $period ? "Periode aktif {$period}." : 'Belum ada periode aktif yang berjalan.';

        return sprintf(
            'Selamat datang, %s (%s). %s Pantau progres KPI, tindak lanjuti assessment, dan jaga performa tim tetap terukur.',
            $user->name,
            $roleLabel,
            $periodLabel,
        );
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
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        $actions = [];

        if (KpiTemplateResource::canCreate()) {
            $actions[] = Action::make('createTemplate')
                ->label('Buat Template KPI')
                ->url(KpiTemplateResource::getUrl('create'))
                ->color('success');
        }

        if (KpiAssignmentResource::canCreate()) {
            $actions[] = Action::make('createAssignment')
                ->label('Assign KPI')
                ->url(KpiAssignmentResource::getUrl('create'))
                ->color('gray');
        }

        if (KpiAssessmentResource::canViewAny()) {
            $actions[] = Action::make('openReviewQueue')
                ->label('Review Assessment')
                ->url(KpiAssessmentResource::getUrl('index'));
        }

        return $actions;
    }
}
