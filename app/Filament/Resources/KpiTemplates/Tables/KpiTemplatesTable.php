<?php

namespace App\Filament\Resources\KpiTemplates\Tables;

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
                    ->label('Items')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->dateTime('d M Y H:i')
                    ->label('Published')
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
            ->defaultSort('year', 'desc')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50])
            ->recordActions([
                ViewAction::make()
                    ->label('Preview'),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
