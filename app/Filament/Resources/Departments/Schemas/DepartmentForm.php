<?php

namespace App\Filament\Resources\Departments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Department Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('division_id')
                                    ->relationship('division', 'name')
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
                                Toggle::make('is_active')
                                    ->required()
                                    ->default(true)
                                    ->inline(false)
                                    ->columnSpan(1),
                                Textarea::make('description')
                                    ->rows(4)
                                    ->maxLength(65535)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
