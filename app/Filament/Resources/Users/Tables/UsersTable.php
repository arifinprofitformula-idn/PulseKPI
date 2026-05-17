<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Full Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee_code')
                    ->label('Employee Code')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge(),
                TextColumn::make('division.name')
                    ->label('Division')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('position.name')
                    ->label('Position')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('employment_status')
                    ->label('Status')
                    ->badge()
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('employment_status')
                    ->label('Employment Status')
                    ->options([
                        'active' => 'Active',
                        'probation' => 'Probation',
                        'contract' => 'Contract',
                        'inactive' => 'Inactive',
                        'terminated' => 'Terminated',
                        'resigned' => 'Resigned',
                        'former' => 'Former',
                    ]),
            ])
            ->defaultSort('name')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
