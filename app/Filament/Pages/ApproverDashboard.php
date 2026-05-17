<?php

namespace App\Filament\Pages;

use App\Enums\SystemRole;
use App\Filament\Resources\KpiAssessmentReports\KpiAssessmentReportResource;
use App\Filament\Resources\KpiAssessments\KpiAssessmentResource;
use App\Filament\Widgets\ApproverApprovalQueueWidget;
use App\Filament\Widgets\ApproverOverviewStatsWidget;
use App\Filament\Widgets\ApproverRecentDecisionsWidget;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

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
        return 'Approver Dashboard';
    }

    public function getHeading(): string
    {
        return 'Approver Dashboard';
    }

    public function getHeader(): ?View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('filament.pages.approver-dashboard-header', [
            'user' => $user,
            'quickActions' => $this->getQuickActions(),
        ]);
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
     * @return list<array{label: string, url: string, style: string}>
     */
    protected function getQuickActions(): array
    {
        $actions = [];

        if (KpiAssessmentResource::canViewAny()) {
            $actions[] = [
                'label' => 'Open Approval Queue',
                'url' => KpiAssessmentResource::getUrl('index'),
                'style' => 'primary',
            ];
        }

        if (KpiAssessmentReportResource::canViewAny()) {
            $actions[] = [
                'label' => 'Open Approval History',
                'url' => KpiAssessmentReportResource::getUrl('index'),
                'style' => 'secondary',
            ];
        }

        return $actions;
    }
}
