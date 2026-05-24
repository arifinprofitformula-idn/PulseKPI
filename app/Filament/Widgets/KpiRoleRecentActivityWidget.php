<?php

namespace App\Filament\Widgets;

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

class KpiRoleRecentActivityWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

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
            ->heading('Recent assessments')
            ->description('Update assessment terbaru yang aman ditampilkan tanpa mengekspos jalur private file apa pun.')
            ->query(
                $user instanceof User
                    ? app(KpiDashboardService::class)->recentAssessmentQuery($user, 5)
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
                TextColumn::make('grade')
                    ->label('Grade')
                    ->badge()
                    ->placeholder('-'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('View detail')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (KpiAssessment $record): string => KpiAssessmentResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated(false)
            ->emptyStateHeading('Belum ada data KPI untuk periode ini.')
            ->emptyStateDescription('Assessment terbaru akan muncul di sini saat aktivitas KPI mulai berjalan.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }
}
