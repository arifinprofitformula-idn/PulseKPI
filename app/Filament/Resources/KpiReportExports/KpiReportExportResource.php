<?php

namespace App\Filament\Resources\KpiReportExports;

use App\Enums\SystemRole;
use App\Filament\Resources\KpiReportExports\Pages\ListKpiReportExports;
use App\Filament\Resources\KpiReportExports\Tables\KpiReportExportsTable;
use App\Models\KpiReportExport;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KpiReportExportResource extends Resource
{
    protected static ?string $model = KpiReportExport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static string|\UnitEnum|null $navigationGroup = 'Exports';

    protected static ?string $navigationLabel = 'Exports';

    protected static ?string $modelLabel = 'Export';

    protected static ?string $pluralModelLabel = 'Exports';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return KpiReportExportsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKpiReportExports::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->can('viewAny', KpiReportExport::class);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['requester', 'assessment.employee']);
        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereKey([]);
        }

        if (
            $user->hasRole(SystemRole::SUPER_ADMIN->value)
            || $user->hasRole(SystemRole::HRD->value)
        ) {
            return $query;
        }

        return $query->where('requested_by', $user->getKey());
    }
}
