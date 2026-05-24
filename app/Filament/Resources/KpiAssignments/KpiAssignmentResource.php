<?php

namespace App\Filament\Resources\KpiAssignments;

use App\Enums\KpiAssignmentStatus;
use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Filament\Resources\KpiAssignments\Pages\CreateKpiAssignment;
use App\Filament\Resources\KpiAssignments\Pages\ListKpiAssignments;
use App\Filament\Resources\KpiAssignments\Pages\ViewKpiAssignment;
use App\Filament\Resources\KpiAssignments\Schemas\KpiAssignmentForm;
use App\Filament\Resources\KpiAssignments\Tables\KpiAssignmentsTable;
use App\Models\KpiAssignment;
use App\Models\User;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KpiAssignmentResource extends Resource
{
    protected static ?string $model = KpiAssignment::class;

    protected static ?string $recordTitleAttribute = 'id';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'KPI Operations';

    protected static ?string $navigationLabel = 'Assignment KPI';

    protected static ?string $modelLabel = 'Assignment KPI';

    protected static ?string $pluralModelLabel = 'Assignment KPI';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return KpiAssignmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Assignment Overview')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('employee.name')
                                    ->label('Employee'),
                                TextEntry::make('employee.employee_code')
                                    ->label('Employee Code')
                                    ->placeholder('-'),
                                TextEntry::make('employee.division.name')
                                    ->label('Division')
                                    ->placeholder('-'),
                                TextEntry::make('employee.department.name')
                                    ->label('Department')
                                    ->placeholder('-'),
                                TextEntry::make('employee.position.name')
                                    ->label('Position')
                                    ->placeholder('-'),
                                TextEntry::make('period.name')
                                    ->label('KPI Period'),
                                TextEntry::make('template.name')
                                    ->label('KPI Template'),
                                TextEntry::make('template.revision')
                                    ->label('Template Revision'),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (mixed $state): string => $state instanceof KpiAssignmentStatus ? $state->label() : KpiAssignmentStatus::from((string) $state)->label()),
                                TextEntry::make('assigned_at')
                                    ->label('Assigned At')
                                    ->dateTime('d M Y H:i')
                                    ->placeholder('-'),
                                TextEntry::make('assignedBy.name')
                                    ->label('Assigned By')
                                    ->placeholder('-'),
                                TextEntry::make('cancelled_at')
                                    ->label('Cancelled At')
                                    ->dateTime('d M Y H:i')
                                    ->placeholder('-'),
                                TextEntry::make('notes')
                                    ->label('Notes')
                                    ->columnSpanFull()
                                    ->placeholder('-'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return KpiAssignmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKpiAssignments::route('/'),
            'create' => CreateKpiAssignment::route('/create'),
            'view' => ViewKpiAssignment::route('/{record}'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        $user = auth()->user();

        return $user instanceof User && ($user->isManager() || $user->isSupervisor())
            ? 'Dashboard'
            : static::$navigationGroup;
    }

    public static function getNavigationLabel(): string
    {
        $user = auth()->user();

        return $user instanceof User && ($user->isManager() || $user->isSupervisor())
            ? 'Team KPI'
            : (static::$navigationLabel ?? 'Assignment KPI');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && (
                $user->can(SystemPermission::ASSIGN_KPI->value)
                || $user->canAssessDirectReports()
                || $user->hasRole(SystemRole::EMPLOYEE->value)
            );
    }

    public static function canView($record): bool
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $record instanceof KpiAssignment) {
            return false;
        }

        if ($user->can(SystemPermission::ASSIGN_KPI->value)
            || $user->hasRole(SystemRole::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->canAssessDirectReports()) {
            return $record->employee !== null && $user->isDirectSupervisorOf($record->employee);
        }

        return $record->employee !== null && $user->is($record->employee);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'period',
                'template',
                'employee.division',
                'employee.department',
                'employee.position',
                'assignedBy',
            ]);

        $user = auth()->user();

        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->can(SystemPermission::ASSIGN_KPI->value)
            || $user->hasRole(SystemRole::SUPER_ADMIN->value)) {
            return $query;
        }

        if ($user->canAssessDirectReports()) {
            return $query->whereHas(
                'employee',
                fn (Builder $q) => $q->where('supervisor_id', $user->getKey())
            );
        }

        return $query->where('employee_id', $user->getKey());
    }
}
