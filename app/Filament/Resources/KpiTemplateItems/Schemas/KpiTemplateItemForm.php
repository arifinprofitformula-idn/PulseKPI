<?php

namespace App\Filament\Resources\KpiTemplateItems\Schemas;

use App\Filament\Resources\KpiScoreRules\Schemas\KpiScoreRuleForm;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KpiTemplateItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('KPI Component Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('sort_order')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1),
                                TextInput::make('weight')
                                    ->label('Weight')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->maxValue(100)
                                    ->step('0.01'),
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Textarea::make('description')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                Textarea::make('target_description')
                                    ->label('Target')
                                    ->required()
                                    ->rows(4)
                                    ->columnSpanFull(),
                                TextInput::make('data_source')
                                    ->label('Data Source')
                                    ->maxLength(255),
                                Toggle::make('is_required')
                                    ->required()
                                    ->default(true)
                                    ->inline(false),
                            ]),
                    ]),
                Section::make('Scoring Rules')
                    ->description('Define how score 0, 1, and 2 are interpreted for this KPI component.')
                    ->schema([
                        Repeater::make('scoreRules')
                            ->relationship()
                            ->label('Scoring Rules')
                            ->defaultItems(0)
                            ->addActionLabel('Add Scoring Rule')
                            ->reorderable(false)
                            ->schema(KpiScoreRuleForm::getComponents())
                            ->columns(1)
                            ->addable(fn (?object $record): bool => $record?->template?->published_at === null)
                            ->deletable(fn (?object $record): bool => $record?->template?->published_at === null)
                            ->cloneable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => filled($state['score'] ?? null) ? 'Score '.$state['score'] : 'New Rule')
                            ->deleteAction(fn (Action $action): Action => $action->requiresConfirmation()),
                    ]),
            ]);
    }
}
