<?php

namespace App\Filament\Widgets;

use App\Enums\KpiAssessmentStatus;
use App\Filament\Pages\HrdDashboard;
use App\Filament\Resources\KpiAssessments\KpiAssessmentResource;
use App\Models\KpiAssessment;
use App\Models\User;
use App\Services\Dashboard\HrdDashboardService;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class HrdPendingReviewWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'xl' => 2,
    ];

    public static function canView(): bool
    {
        return HrdDashboard::canAccess();
    }

    public function table(Table $table): Table
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $table
            ->heading('Pending review')
            ->description('Assessment terbaru yang menunggu review HRD.')
            ->query(
                $user instanceof User
                    ? app(HrdDashboardService::class)->pendingReviewQuery($user, 5)
                    : KpiAssessment::query()->whereKey([])
            )
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->searchable(),
                TextColumn::make('assignment.period.name')
                    ->label('Periode')
                    ->placeholder('-'),
                TextColumn::make('assignment.template.name')
                    ->label('Template')
                    ->toggleable(),
                TextColumn::make('assessor.name')
                    ->label('Assessor')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof KpiAssessmentStatus ? $state->label() : KpiAssessmentStatus::from((string) $state)->label())
                    ->color('warning'),
                TextColumn::make('submitted_at')
                    ->label('Disubmit')
                    ->since()
                    ->placeholder('-'),
            ])
            ->recordActions([
                Action::make('review')
                    ->label('Buka review')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (KpiAssessment $record): string => KpiAssessmentResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated(false)
            ->emptyStateHeading('Belum ada assessment yang menunggu review.')
            ->emptyStateDescription('Begitu manager mengirim assessment, antrian review akan tampil di sini.')
            ->emptyStateIcon('heroicon-o-inbox');
    }
}
