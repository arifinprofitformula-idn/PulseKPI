<?php

namespace App\Filament\Widgets;

use App\Enums\KpiAssessmentStatus;
use App\Filament\Pages\ManagerDashboard;
use App\Filament\Resources\KpiAssessments\KpiAssessmentResource;
use App\Models\KpiAssessment;
use App\Models\User;
use App\Services\Dashboard\ManagerDashboardService;
use App\Support\KpiStatusBadge;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ManagerAssessmentQueueWidget extends TableWidget
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
            ->heading('Action queue')
            ->description('Prioritas kerja Anda: draft, revisi rejected, dan item submitted yang masih perlu dipantau.')
            ->query(
                $user instanceof User
                    ? app(ManagerDashboardService::class)->assessmentQueueQuery($user, 5)
                    : KpiAssessment::query()->whereKey([])
            )
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->searchable(),
                TextColumn::make('assignment.period.name')
                    ->label('Period')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => KpiStatusBadge::assessmentLabel($state))
                    ->color(fn (mixed $state): string => KpiStatusBadge::assessmentColor($state)),
                TextColumn::make('final_score')
                    ->label('Final score')
                    ->formatStateUsing(fn (mixed $state): string => filled($state) ? number_format((float) $state, 2) : '-'),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since(),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(function (KpiAssessment $record): string {
                        return match ($record->status) {
                            KpiAssessmentStatus::DRAFT => 'Continue draft',
                            KpiAssessmentStatus::REJECTED => 'Revise and resubmit',
                            default => 'View detail',
                        };
                    })
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (KpiAssessment $record): string => KpiAssessmentResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated(false)
            ->emptyStateHeading('Tidak ada assessment yang membutuhkan tindakan saat ini.')
            ->emptyStateDescription('Semua pekerjaan sudah tertangani. Draft, revisi, dan submission baru akan muncul di sini saat workflow bergerak.')
            ->emptyStateIcon('heroicon-o-inbox');
    }
}
