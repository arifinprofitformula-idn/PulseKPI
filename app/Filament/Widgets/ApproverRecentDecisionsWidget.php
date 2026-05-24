<?php

namespace App\Filament\Widgets;

use App\Enums\KpiApprovalAction;
use App\Filament\Pages\ApproverDashboard;
use App\Filament\Resources\KpiAssessments\KpiAssessmentResource;
use App\Models\KpiApproval;
use App\Models\User;
use App\Services\Dashboard\ApproverDashboardService;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ApproverRecentDecisionsWidget extends TableWidget
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
            ->heading('Recent activity')
            ->description('Approve, reject, dan lock terbaru. Record yang tidak lagi boleh dibuka tetap dimasking sesuai workflow.')
            ->query(
                $user instanceof User
                    ? app(ApproverDashboardService::class)->recentDecisionQuery($user, 5)
                    : KpiApproval::query()->whereKey([])
            )
            ->columns([
                TextColumn::make('action')
                    ->label('Decision')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof KpiApprovalAction ? $state->label() : KpiApprovalAction::from((string) $state)->label())
                    ->color(fn (mixed $state): string => match ($state instanceof KpiApprovalAction ? $state : KpiApprovalAction::from((string) $state)) {
                        KpiApprovalAction::APPROVED => 'success',
                        KpiApprovalAction::REJECTED => 'danger',
                        KpiApprovalAction::LOCKED => 'slate',
                        default => 'indigo',
                    }),
                TextColumn::make('assessment.employee.name')
                    ->label('Employee')
                    ->formatStateUsing(function (mixed $state, KpiApproval $record): string {
                        return auth()->user()?->can('view', $record->assessment)
                            ? ((string) ($state ?: '-'))
                            : 'Hidden by workflow scope';
                    }),
                TextColumn::make('assessment.assignment.period.name')
                    ->label('Period')
                    ->formatStateUsing(function (mixed $state, KpiApproval $record): string {
                        return auth()->user()?->can('view', $record->assessment)
                            ? ((string) ($state ?: '-'))
                            : '-';
                    }),
                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(40)
                    ->placeholder('-'),
                TextColumn::make('acted_at')
                    ->label('Decided')
                    ->since(),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('View detail')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->visible(fn (KpiApproval $record): bool => auth()->user()?->can('view', $record->assessment) ?? false)
                    ->url(fn (KpiApproval $record): string => KpiAssessmentResource::getUrl('edit', ['record' => $record->assessment])),
            ])
            ->paginated(false)
            ->emptyStateHeading('Semua pekerjaan sudah tertangani.')
            ->emptyStateDescription('Aktivitas approval terbaru akan muncul di sini setelah Anda mengambil keputusan pada assessment reviewed.')
            ->emptyStateIcon('heroicon-o-check-badge');
    }
}
