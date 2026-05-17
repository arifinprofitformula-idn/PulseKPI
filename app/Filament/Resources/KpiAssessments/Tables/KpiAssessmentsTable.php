<?php

namespace App\Filament\Resources\KpiAssessments\Tables;

use App\Enums\KpiAssessmentStatus;
use App\Enums\SystemRole;
use App\Models\User;
use App\Support\KpiStatusBadge;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KpiAssessmentsTable
{
    public static function configure(Table $table): Table
    {
        /** @var User|null $user */
        $user = auth()->user();

        $emptyStateHeading = match (true) {
            $user instanceof User && $user->hasRole(SystemRole::APPROVER->value) => 'No approval items are available',
            $user instanceof User && $user->isManager() => 'No team assessments are available yet',
            default => 'No KPI assessments yet',
        };

        $emptyStateDescription = match (true) {
            $user instanceof User && $user->hasRole(SystemRole::APPROVER->value) => 'Only reviewed, approved, and locked assessments within your workflow scope will appear here.',
            $user instanceof User && $user->isManager() => 'Create assessments from assigned KPI records that belong to your direct reports.',
            default => 'Create assessments from assigned KPI records that are ready for manager review.',
        };

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
                    ->formatStateUsing(fn (mixed $state): string => KpiStatusBadge::assessmentLabel($state))
                    ->color(fn (mixed $state): string => KpiStatusBadge::assessmentColor($state)),
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
            ->emptyStateHeading($emptyStateHeading)
            ->emptyStateDescription($emptyStateDescription)
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }
}
