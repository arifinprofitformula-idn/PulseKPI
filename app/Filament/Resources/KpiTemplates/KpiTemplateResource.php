<?php

namespace App\Filament\Resources\KpiTemplates;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Filament\Resources\KpiTemplates\Pages\CreateKpiTemplate;
use App\Filament\Resources\KpiTemplates\Pages\EditKpiTemplate;
use App\Filament\Resources\KpiTemplates\Pages\ListKpiTemplates;
use App\Filament\Resources\KpiTemplates\Pages\ViewKpiTemplate;
use App\Filament\Resources\KpiTemplates\RelationManagers\KpiTemplateItemsRelationManager;
use App\Filament\Resources\KpiTemplates\Schemas\KpiTemplateForm;
use App\Filament\Resources\KpiTemplates\Tables\KpiTemplatesTable;
use App\Models\KpiTemplate;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KpiTemplateResource extends Resource
{
    protected static ?string $model = KpiTemplate::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'KPI Management';

    protected static ?string $navigationLabel = 'KPI Templates';

    public static function form(Schema $schema): Schema
    {
        return KpiTemplateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template Preview')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name'),
                                TextEntry::make('code'),
                                TextEntry::make('year'),
                                TextEntry::make('revision'),
                                TextEntry::make('division.name')
                                    ->placeholder('All divisions'),
                                TextEntry::make('department.name')
                                    ->placeholder('All departments'),
                                TextEntry::make('position.name')
                                    ->placeholder('All positions'),
                                TextEntry::make('published_at')
                                    ->dateTime('d M Y H:i')
                                    ->placeholder('Draft'),
                                TextEntry::make('is_active')
                                    ->badge()
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive'),
                                TextEntry::make('total_weight')
                                    ->state(fn (KpiTemplate $record): string => number_format((float) $record->items()->sum('weight'), 2))
                                    ->label('Total Weight'),
                                TextEntry::make('description')
                                    ->columnSpanFull()
                                    ->placeholder('-'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return KpiTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            KpiTemplateItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKpiTemplates::route('/'),
            'create' => CreateKpiTemplate::route('/create'),
            'view' => ViewKpiTemplate::route('/{record}'),
            'edit' => EditKpiTemplate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['division', 'department', 'position'])
            ->withCount('items');

        $user = auth()->user();

        if ($user !== null
            && $user->hasRole(SystemRole::MANAGER->value)
            && ! $user->can(SystemPermission::MANAGE_KPI_TEMPLATES->value)) {
            $query
                ->where('is_active', true)
                ->whereNotNull('published_at');
        }

        return $query;
    }
}
