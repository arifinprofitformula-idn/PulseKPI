<?php

namespace App\Filament\Resources\KpiAssessments;

use App\Enums\SystemRole;
use App\Filament\Resources\KpiAssessments\Pages\EditKpiAssessment;
use App\Filament\Resources\KpiAssessments\Pages\ListKpiAssessments;
use App\Filament\Resources\KpiAssessments\Schemas\KpiAssessmentForm;
use App\Filament\Resources\KpiAssessments\Tables\KpiAssessmentsTable;
use App\Models\KpiAssessment;
use App\Models\KpiAssignment;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KpiAssessmentResource extends Resource
{
    protected static ?string $model = KpiAssessment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'KPI Operations';

    protected static ?string $navigationLabel = 'Assessment KPI';

    protected static ?string $modelLabel = 'Assessment KPI';

    protected static ?string $pluralModelLabel = 'Assessment KPI';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return KpiAssessmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KpiAssessmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKpiAssessments::route('/'),
            'edit' => EditKpiAssessment::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        $user = auth()->user();

        if ($user instanceof User && ($user->isManager() || $user->hasRole(SystemRole::APPROVER->value))) {
            return 'Dashboard';
        }

        return static::$navigationGroup;
    }

    public static function getNavigationLabel(): string
    {
        $user = auth()->user();

        if ($user instanceof User && $user->hasRole(SystemRole::APPROVER->value)) {
            return 'Approval Queue';
        }

        if ($user instanceof User && $user->isManager()) {
            return 'Assessment Queue';
        }

        return static::$navigationLabel ?? 'Assessment KPI';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'assignment.period',
                'assignment.template',
                'employee.division',
                'employee.department',
                'employee.position',
                'assessor',
                'items.templateItem',
                'attendanceAdjustment',
                'approvals.actor',
            ]);

        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        return $query->visibleToUser($user);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->count();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * @return array<int, string>
     */
    public static function eligibleAssignmentOptions(?User $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        return static::eligibleAssignmentsQuery($user)
            ->with(['employee', 'period', 'template'])
            ->orderByDesc('assigned_at')
            ->get()
            ->mapWithKeys(fn (KpiAssignment $assignment): array => [
                $assignment->getKey() => sprintf(
                    '%s | %s | %s',
                    $assignment->employee?->name ?? 'Unknown employee',
                    $assignment->period?->name ?? 'No period',
                    $assignment->template?->name ?? 'No template'
                ),
            ])
            ->all();
    }

    public static function eligibleAssignmentsQuery(User $user): Builder
    {
        $query = KpiAssignment::query()
            ->with(['employee', 'period', 'template'])
            ->where('status', 'assigned')
            ->whereDoesntHave('assessment');

        if ($user->hasRole(SystemRole::SUPER_ADMIN->value)) {
            return $query;
        }

        if ($user->isManager()) {
            return $query->whereHas(
                'employee',
                fn (Builder $builder) => $builder
                    ->where('supervisor_id', $user->getKey())
                    ->whereKeyNot($user->getKey())
            );
        }

        return $query->whereRaw('1 = 0');
    }
}
