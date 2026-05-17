<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\ApproverDashboard;
use App\Filament\Resources\KpiAssessments\KpiAssessmentResource;
use App\Models\KpiAssessment;
use App\Models\User;
use App\Services\Dashboard\ApproverDashboardService;
use App\Support\KpiStatusBadge;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ApproverApprovalQueueWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return ApproverDashboard::canAccess();
    }

    public function table(Table $table): Table
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $table
            ->heading('Approval queue')
            ->description('Only reviewed assessments are listed here, matching the current approval workflow.')
            ->query(
                $user instanceof User
                    ? app(ApproverDashboardService::class)->approvalQueueQuery($user, 6)
                    : KpiAssessment::query()->whereKey([])
            )
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->searchable(),
                TextColumn::make('assignment.period.name')
                    ->label('Period')
                    ->placeholder('-'),
                TextColumn::make('assessor.name')
                    ->label('Reviewer')
                    ->placeholder('-'),
                TextColumn::make('final_score')
                    ->label('Final score')
                    ->formatStateUsing(fn (mixed $state): string => filled($state) ? number_format((float) $state, 2) : '-'),
                TextColumn::make('grade')
                    ->label('Grade')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => KpiStatusBadge::assessmentLabel($state))
                    ->color(fn (mixed $state): string => KpiStatusBadge::assessmentColor($state)),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Review decision')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (KpiAssessment $record): string => KpiAssessmentResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated(false)
            ->emptyStateHeading('No pending approval right now.')
            ->emptyStateDescription('Reviewed assessments will appear here as soon as they are ready for approver sign-off.')
            ->emptyStateIcon('heroicon-o-inbox');
    }
}
