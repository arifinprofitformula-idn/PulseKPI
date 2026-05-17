<?php

namespace App\Filament\Resources\KpiReportExports\Tables;

use App\Enums\KpiReportExportStatus;
use App\Models\KpiReportExport;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KpiReportExportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state, KpiReportExport $record): string => $record->type->label())
                    ->badge(),
                TextColumn::make('format')
                    ->label('Format')
                    ->formatStateUsing(fn ($state, KpiReportExport $record): string => $record->format->label())
                    ->badge(),
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
                    ->label('Requested By')
                    ->toggleable(),
                TextColumn::make('file_name')
                    ->label('File Name')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('total_rows')
                    ->label('Rows')
                    ->numeric()
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Requested At')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('finished_at')
                    ->label('Finished At')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (KpiReportExport $record): bool => auth()->user()?->can('download', $record) ?? false)
                    ->url(fn (KpiReportExport $record): string => route('kpi-report-exports.download', $record)),
            ])
            ->toolbarActions([])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50]);
    }
}
