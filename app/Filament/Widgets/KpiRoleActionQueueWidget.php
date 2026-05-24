<?php

namespace App\Filament\Widgets;

use App\Enums\KpiAssessmentStatus;
use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Filament\Resources\KpiAssessments\KpiAssessmentResource;
use App\Models\KpiAssessment;
use App\Models\User;
use App\Services\Dashboard\KpiDashboardService;
use App\Support\KpiStatusBadge;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class KpiRoleActionQueueWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'xl' => 2,
    ];

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->hasRole(SystemRole::SUPER_ADMIN->value)
                || $user->hasRole(SystemRole::HRD->value)
                || $user->can(SystemPermission::VIEW_REPORTS->value));
    }

    public function table(Table $table): Table
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $table
            ->heading('Action queue')
            ->description('Assessment submitted dan reviewed yang paling membutuhkan tindak lanjut saat ini.')
            ->query(
                $user instanceof User
                    ? app(KpiDashboardService::class)->actionQueueQuery($user, 5)
                    : KpiAssessment::query()->whereKey([])
            )
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->searchable(),
                TextColumn::make('assignment.period.name')
                    ->label('Period')
                    ->placeholder('-'),
                TextColumn::make('assignment.template.name')
                    ->label('Template')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => KpiStatusBadge::assessmentLabel($state))
                    ->color(fn (mixed $state): string => KpiStatusBadge::assessmentColor($state)),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since(),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(fn (KpiAssessment $record): string => $record->status === KpiAssessmentStatus::SUBMITTED ? 'Review now' : 'Open detail')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (KpiAssessment $record): string => KpiAssessmentResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated(false)
            ->emptyStateHeading('Tidak ada assessment yang membutuhkan tindakan saat ini.')
            ->emptyStateDescription('Semua pekerjaan sudah tertangani.')
            ->emptyStateIcon('heroicon-o-inbox');
    }
}
