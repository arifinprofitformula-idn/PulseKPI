<?php

namespace App\Filament\Resources\KpiAssignments\Tables;

use App\Enums\KpiAssignmentStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KpiAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.division.name')
                    ->label('Division')
                    ->toggleable()
                    ->placeholder('-'),
                TextColumn::make('employee.department.name')
                    ->label('Department')
                    ->toggleable()
                    ->placeholder('-'),
                TextColumn::make('employee.position.name')
                    ->label('Position')
                    ->toggleable()
                    ->placeholder('-'),
                TextColumn::make('period.name')
                    ->label('Period')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('template.name')
                    ->label('Template')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof KpiAssignmentStatus ? $state->label() : KpiAssignmentStatus::from((string) $state)->label())
                    ->color(fn (mixed $state): string => match (
                        $state instanceof KpiAssignmentStatus ? $state : KpiAssignmentStatus::from((string) $state)
                    ) {
                        KpiAssignmentStatus::ASSIGNED => 'success',
                        KpiAssignmentStatus::CANCELLED => 'danger',
                        KpiAssignmentStatus::DRAFT => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('assigned_at')
                    ->label('Assigned At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('assignedBy.name')
                    ->label('Assigned By')
                    ->toggleable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('kpi_period_id')
                    ->label('Period')
                    ->relationship('period', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('kpi_template_id')
                    ->label('Template')
                    ->relationship('template', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(KpiAssignmentStatus::options()),
            ])
            ->defaultSort('assigned_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50])
            ->recordActions([
                ViewAction::make(),
            ])
            ->emptyStateHeading('No KPI assignments yet')
            ->emptyStateDescription('Assign published KPI templates to employees.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }
}
