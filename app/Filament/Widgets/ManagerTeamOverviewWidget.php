<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\ManagerDashboard;
use App\Filament\Resources\KpiAssessments\KpiAssessmentResource;
use App\Filament\Resources\KpiAssignments\KpiAssignmentResource;
use App\Models\KpiAssignment;
use App\Models\User;
use App\Services\Dashboard\ManagerDashboardService;
use App\Support\KpiStatusBadge;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ManagerTeamOverviewWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return ManagerDashboard::canAccess();
    }

    public function table(Table $table): Table
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $table
            ->heading('Team KPI overview')
            ->description('Latest KPI cycle per direct subordinate, including score and next action context.')
            ->query(
                $user instanceof User
                    ? app(ManagerDashboardService::class)->teamOverviewQuery($user, 6)
                    : KpiAssignment::query()->whereKey([])
            )
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Team member')
                    ->searchable(),
                TextColumn::make('period.name')
                    ->label('Period')
                    ->placeholder('-'),
                TextColumn::make('assessment.status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => filled($state) ? KpiStatusBadge::assessmentLabel($state) : 'Assigned')
                    ->color(fn (mixed $state): string => filled($state) ? KpiStatusBadge::assessmentColor($state) : 'info'),
                TextColumn::make('assessment.final_score')
                    ->label('Final score')
                    ->formatStateUsing(fn (mixed $state): string => filled($state) ? number_format((float) $state, 2) : '-'),
                TextColumn::make('assessment.grade')
                    ->label('Grade')
                    ->badge()
                    ->placeholder('-'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(function (KpiAssignment $record): string {
                        if ($record->assessment !== null) {
                            return $record->assessment->status->value === 'rejected' ? 'Revise assessment' : 'View detail';
                        }

                        return 'View assignment';
                    })
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(function (KpiAssignment $record): string {
                        if ($record->assessment !== null) {
                            return KpiAssessmentResource::getUrl('edit', ['record' => $record->assessment]);
                        }

                        return KpiAssignmentResource::getUrl('view', ['record' => $record]);
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading('No team members in your dashboard scope yet.')
            ->emptyStateDescription('Once employees report to you and receive KPI assignments, their latest status will appear here.')
            ->emptyStateIcon('heroicon-o-user-group');
    }
}
