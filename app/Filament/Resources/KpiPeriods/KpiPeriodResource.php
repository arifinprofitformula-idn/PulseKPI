<?php

namespace App\Filament\Resources\KpiPeriods;

use App\Filament\Resources\KpiPeriods\Pages\CreateKpiPeriod;
use App\Filament\Resources\KpiPeriods\Pages\EditKpiPeriod;
use App\Filament\Resources\KpiPeriods\Pages\ListKpiPeriods;
use App\Filament\Resources\KpiPeriods\Schemas\KpiPeriodForm;
use App\Filament\Resources\KpiPeriods\Tables\KpiPeriodsTable;
use App\Models\KpiPeriod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KpiPeriodResource extends Resource
{
    protected static ?string $model = KpiPeriod::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static string|\UnitEnum|null $navigationGroup = 'KPI Operations';

    protected static ?string $navigationLabel = 'Periode KPI';

    protected static ?string $modelLabel = 'Periode KPI';

    protected static ?string $pluralModelLabel = 'Periode KPI';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return KpiPeriodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KpiPeriodsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKpiPeriods::route('/'),
            'create' => CreateKpiPeriod::route('/create'),
            'edit' => EditKpiPeriod::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('assignments');
    }
}
