<?php

namespace App\Filament\Resources\Divisions\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DivisionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Division Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),
                                TextInput::make('code')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->columnSpan(1),
                                Textarea::make('description')
                                    ->rows(4)
                                    ->maxLength(65535)
                                    ->columnSpanFull(),
                                Toggle::make('is_active')
                                    ->required()
                                    ->default(true)
                                    ->inline(false)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
