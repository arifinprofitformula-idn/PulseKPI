<?php

namespace App\Filament\Resources\Positions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PositionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Position Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('department_id')
                                    ->relationship('department', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(1),
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),
                                TextInput::make('code')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->columnSpan(1),
                                TextInput::make('level')
                                    ->maxLength(255)
                                    ->columnSpan(1),
                                Toggle::make('is_active')
                                    ->required()
                                    ->default(true)
                                    ->inline(false)
                                    ->columnSpanFull(),
                                Textarea::make('description')
                                    ->rows(4)
                                    ->maxLength(65535)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
