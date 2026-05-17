<?php

namespace App\Filament\Widgets;

use App\Enums\KpiReportExportStatus;
use App\Filament\Pages\HrdDashboard;
use App\Models\KpiReportExport;
use App\Models\User;
use App\Services\Dashboard\HrdDashboardService;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class HrdLatestExportsWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'xl' => 2,
    ];

    public static function canView(): bool
    {
        return HrdDashboard::canAccess();
    }

    public function table(Table $table): Table
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $table
            ->heading('Export terbaru')
            ->description('Riwayat export terakhir tanpa menampilkan path file private.')
            ->query(
                $user instanceof User
                    ? app(HrdDashboardService::class)->latestExportsQuery($user, 5)
                    : KpiReportExport::query()->whereKey([])
            )
            ->columns([
                TextColumn::make('type')
                    ->label('Tipe')
                    ->formatStateUsing(fn ($state, KpiReportExport $record): string => $record->type->label())
                    ->badge(),
                TextColumn::make('format')
                    ->label('Format')
                    ->formatStateUsing(fn ($state, KpiReportExport $record): string => $record->format->label())
                    ->badge()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state, KpiReportExport $record): string => $record->status->label())
                    ->color(fn ($state, KpiReportExport $record): string => match ($record->status) {
                        KpiReportExportStatus::PENDING => 'warning',
                        KpiReportExportStatus::PROCESSING => 'info',
                        KpiReportExportStatus::COMPLETED => 'success',
                        KpiReportExportStatus::FAILED => 'danger',
                    }),
                TextColumn::make('requester.name')
                    ->label('Peminta')
                    ->placeholder('-'),
                TextColumn::make('file_name')
                    ->label('File')
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->since(),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (KpiReportExport $record): bool => auth()->user()?->can('download', $record) ?? false)
                    ->url(fn (KpiReportExport $record): string => route('kpi-report-exports.download', $record)),
            ])
            ->paginated(false)
            ->emptyStateHeading('Belum ada export terbaru.')
            ->emptyStateDescription('Export KPI akan muncul di sini setelah laporan diproses.')
            ->emptyStateIcon('heroicon-o-arrow-down-tray');
    }
}
