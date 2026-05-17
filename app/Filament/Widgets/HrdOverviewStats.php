<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\HrdDashboard;
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

    protected ?string $description = 'Pantau metrik utama HRD secara cepat tanpa meninggalkan panel utama.';

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

        $metrics = app(HrdDashboardService::class)->getSummaryMetrics($user);

        return [
            Stat::make('Total karyawan', number_format($metrics['total_employees']))
                ->description('Karyawan dalam cakupan dashboard')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make('Template aktif', number_format($metrics['active_templates']))
                ->description('Template siap dipakai')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('success'),
            Stat::make('Assignment aktif', number_format($metrics['active_assignments']))
                ->description('Siklus KPI yang sedang berjalan')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('info'),
            Stat::make('Pending review', number_format($metrics['pending_review']))
                ->description('Assessment menunggu review HRD')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('Menunggu approval', number_format($metrics['waiting_approval']))
                ->description('Sudah direview dan menunggu approver')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('indigo'),
            Stat::make('Approved', number_format($metrics['approved']))
                ->description('Assessment yang sudah disetujui')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Locked', number_format($metrics['locked']))
                ->description('Assessment final dan terkunci')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('gray'),
        ];
    }
}
