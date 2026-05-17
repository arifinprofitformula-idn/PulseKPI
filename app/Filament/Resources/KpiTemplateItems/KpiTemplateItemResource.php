<?php

namespace App\Filament\Resources\KpiTemplateItems;

use App\Filament\Resources\KpiTemplateItems\Pages\EditKpiTemplateItem;
use App\Filament\Resources\KpiTemplateItems\Pages\ViewKpiTemplateItem;
use App\Filament\Resources\KpiTemplateItems\Schemas\KpiTemplateItemForm;
use App\Models\KpiTemplateItem;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KpiTemplateItemResource extends Resource
{
    protected static ?string $model = KpiTemplateItem::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return KpiTemplateItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Item Preview')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('template.name')
                                    ->label('Template'),
                                TextEntry::make('sort_order'),
                                TextEntry::make('name')
                                    ->columnSpanFull(),
                                TextEntry::make('weight'),
                                TextEntry::make('is_required')
                                    ->badge()
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Required' : 'Optional'),
                                TextEntry::make('data_source')
                                    ->placeholder('-'),
                                TextEntry::make('description')
                                    ->columnSpanFull()
                                    ->placeholder('-'),
                                TextEntry::make('target_description')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'view' => ViewKpiTemplateItem::route('/{record}'),
            'edit' => EditKpiTemplateItem::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['template', 'scoreRules']);
    }
}
