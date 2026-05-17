<?php

namespace App\Filament\Resources\KpiAssessments\Tables;

use App\Enums\KpiAssessmentStatus;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KpiAssessmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('assignment.period.name')
                    ->label('Period')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('assignment.template.name')
                    ->label('Template')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('assessor.name')
                    ->label('Assessor')
                    ->toggleable()
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof KpiAssessmentStatus ? $state->label() : KpiAssessmentStatus::from((string) $state)->label())
                    ->color(fn (mixed $state): string => match ($state instanceof KpiAssessmentStatus ? $state : KpiAssessmentStatus::from((string) $state)) {
                        KpiAssessmentStatus::DRAFT => 'warning',
                        KpiAssessmentStatus::SUBMITTED => 'success',
                        KpiAssessmentStatus::REJECTED => 'danger',
                    }),
                TextColumn::make('kpi_score')
                    ->label('KPI Score')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('final_score')
                    ->label('Final Score')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('grade')
                    ->label('Grade')
                    ->badge(),
                TextColumn::make('submitted_at')
                    ->label('Submitted At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(KpiAssessmentStatus::options()),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Open'),
            ])
            ->defaultSort('updated_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50])
            ->emptyStateHeading('No KPI assessments yet')
            ->emptyStateDescription('Create assessments from assigned KPI records that are ready for manager review.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }
}
