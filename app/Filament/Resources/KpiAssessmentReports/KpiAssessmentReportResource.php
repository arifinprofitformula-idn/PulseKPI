<?php

namespace App\Filament\Resources\KpiAssessmentReports;

use App\Actions\Reports\BuildKpiAssessmentReportQuery;
use App\Enums\KpiAssessmentStatus;
use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Filament\Resources\KpiAssessmentReports\Pages\ListKpiAssessmentReports;
use App\Filament\Resources\KpiAssessmentReports\Tables\KpiAssessmentReportsTable;
use App\Models\Department;
use App\Models\Division;
use App\Models\KpiAssessment;
use App\Models\KpiPeriod;
use App\Models\Position;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KpiAssessmentReportResource extends Resource
{
    protected static ?string $model = KpiAssessment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Laporan KPI';

    protected static ?string $modelLabel = 'Laporan KPI';

    protected static ?string $pluralModelLabel = 'Laporan KPI';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return KpiAssessmentReportsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKpiAssessmentReports::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->hasRole(SystemRole::SUPER_ADMIN->value) || $user->can(SystemPermission::VIEW_REPORTS->value));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return app(BuildKpiAssessmentReportQuery::class)->execute($user);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->count();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * @return array<int, string>
     */
    public static function periodOptions(?User $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        return KpiPeriod::query()
            ->whereHas('assignments.assessment', fn (Builder $builder) => $builder->visibleToUser($user))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function divisionOptions(?User $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        return Division::query()
            ->whereHas('users.kpiAssessments', fn (Builder $builder) => $builder->visibleToUser($user))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function departmentOptions(?User $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        return Department::query()
            ->whereHas('users.kpiAssessments', fn (Builder $builder) => $builder->visibleToUser($user))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function positionOptions(?User $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        return Position::query()
            ->whereHas('users.kpiAssessments', fn (Builder $builder) => $builder->visibleToUser($user))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function assessorOptions(?User $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        return User::query()
            ->whereHas('assessedKpiAssessments', fn (Builder $builder) => $builder->visibleToUser($user))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public static function yearOptions(?User $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        $periodIds = array_keys(static::periodOptions($user));

        if ($periodIds === []) {
            return [];
        }

        return KpiPeriod::query()
            ->whereIn('id', $periodIds)
            ->orderByDesc('year')
            ->pluck('year', 'year')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(?User $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        if ($user->hasRole(SystemRole::APPROVER->value)) {
            return [
                KpiAssessmentStatus::REVIEWED->value => KpiAssessmentStatus::REVIEWED->label(),
                KpiAssessmentStatus::APPROVED->value => KpiAssessmentStatus::APPROVED->label(),
                KpiAssessmentStatus::LOCKED->value => KpiAssessmentStatus::LOCKED->label(),
            ];
        }

        return KpiAssessmentStatus::options();
    }
}
