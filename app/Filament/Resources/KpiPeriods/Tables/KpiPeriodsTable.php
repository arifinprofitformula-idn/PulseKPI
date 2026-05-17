<?php

namespace App\Filament\Resources\KpiPeriods\Tables;

use App\Enums\KpiPeriodType;
use App\Models\KpiPeriod;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class KpiPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Period Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof KpiPeriodType ? $state->label() : KpiPeriodType::from((string) $state)->label())
                    ->badge()
                    ->sortable(),
                TextColumn::make('year')
                    ->label('Year')
                    ->sortable(),
                TextColumn::make('month')
                    ->label('Month')
                    ->formatStateUsing(fn (?int $state): string => $state !== null ? date('F', mktime(0, 0, 0, $state, 1)) : '-')
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Starts At')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Ends At')
                    ->date('d M Y')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('assignments_count')
                    ->label('Assignments')
                    ->counts('assignments')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(KpiPeriodType::options()),
                SelectFilter::make('year')
                    ->label('Year')
                    ->options(fn (): array => KpiPeriod::query()
                        ->distinct()
                        ->orderByDesc('year')
                        ->pluck('year', 'year')
                        ->all()),
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->defaultSort('year', 'desc')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No KPI periods yet')
            ->emptyStateDescription('Create periods to begin assigning KPI templates to employees.')
            ->emptyStateIcon('heroicon-o-calendar');
    }
}
