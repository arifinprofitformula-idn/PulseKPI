<?php

namespace App\Filament\Resources\KpiTemplates\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class KpiTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('year')
                    ->sortable(),
                TextColumn::make('revision')
                    ->sortable(),
                TextColumn::make('division.name')
                    ->label('Division')
                    ->toggleable(),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->toggleable(),
                TextColumn::make('position.name')
                    ->label('Position')
                    ->toggleable(),
                TextColumn::make('items_count')
                    ->label('KPI Components')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->dateTime('d M Y H:i')
                    ->label('Published At')
                    ->sortable()
                    ->placeholder('Draft'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
                TernaryFilter::make('published_at')
                    ->label('Published')
                    ->nullable(),
            ])
            ->emptyStateHeading('No KPI templates yet')
            ->emptyStateDescription('KPI templates define the components and scoring rules needed before KPI assignment.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Create KPI Template')
                    ->url('/admin/kpi-templates/create')
                    ->icon('heroicon-o-plus')
                    ->button(),
            ])
            ->defaultSort('year', 'desc')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50])
            ->recordActions([
                ViewAction::make()
                    ->label('Preview'),
                EditAction::make()
                    ->label('Edit'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
