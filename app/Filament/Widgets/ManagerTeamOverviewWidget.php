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
            ->heading('Recent team assessments')
            ->description('Update terbaru dari direct report Anda, lengkap dengan status, score, dan grade yang aman ditampilkan.')
            ->query(
                $user instanceof User
                    ? app(ManagerDashboardService::class)->teamOverviewQuery($user, 5)
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
            ->emptyStateHeading('Belum ada data KPI untuk periode ini.')
            ->emptyStateDescription('Saat direct report Anda sudah menerima assignment KPI, ringkasan terbarunya akan muncul di sini.')
            ->emptyStateIcon('heroicon-o-user-group');
    }
}
