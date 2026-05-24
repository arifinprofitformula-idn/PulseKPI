<?php

namespace App\Filament\Pages;

use App\Enums\SystemRole;
use App\Filament\Resources\KpiAssessmentReports\KpiAssessmentReportResource;
use App\Filament\Resources\KpiAssessments\KpiAssessmentResource;
use App\Filament\Widgets\ApproverApprovalQueueWidget;
use App\Filament\Widgets\ApproverOverviewStatsWidget;
use App\Filament\Widgets\ApproverRecentDecisionsWidget;
use App\Models\User;
use App\Services\Dashboard\ApproverDashboardService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ApproverDashboard extends Page
{
    protected static ?string $slug = 'approver-dashboard';

    protected static \UnitEnum|string|null $navigationGroup = 'Dashboard';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 0;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->hasRole(SystemRole::APPROVER->value) || $user->hasRole(SystemRole::SUPER_ADMIN->value));
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasRole(SystemRole::APPROVER->value);
    }

    public static function getNavigationLabel(): string
    {
        return 'Approver Dashboard';
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
        $period = app(ApproverDashboardService::class)->getActivePeriodLabel();

        if (! $user instanceof User) {
            return 'Pantau progres KPI, tindak lanjuti assessment, dan jaga performa tim tetap terukur.';
        }

        $periodLabel = $period ? "Periode aktif {$period}." : 'Belum ada periode aktif yang berjalan.';

        return sprintf(
            'Selamat datang, %s. Dashboard ini hanya menampilkan approval queue sesuai workflow Anda. %s Pantau progres KPI, tindak lanjuti assessment, dan jaga performa tim tetap terukur.',
            $user->name,
            $periodLabel,
        );
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ApproverOverviewStatsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    protected function getFooterWidgets(): array
    {
        return [
            ApproverApprovalQueueWidget::class,
            ApproverRecentDecisionsWidget::class,
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

        if (KpiAssessmentResource::canViewAny()) {
            $actions[] = Action::make('openApprovalQueue')
                ->label('Open Approval Queue')
                ->url(KpiAssessmentResource::getUrl('index'));
        }

        if (KpiAssessmentReportResource::canViewAny()) {
            $actions[] = Action::make('openApprovalHistory')
                ->label('Open Approval History')
                ->url(KpiAssessmentReportResource::getUrl('index'))
                ->color('success');
        }

        return $actions;
    }
}
