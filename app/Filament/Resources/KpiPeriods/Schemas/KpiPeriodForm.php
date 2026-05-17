<?php

namespace App\Filament\Resources\KpiPeriods\Schemas;

use App\Enums\KpiPeriodType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class KpiPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Period Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Period Name')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('type')
                                    ->label('Period Type')
                                    ->options(KpiPeriodType::options())
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn ($set) => $set('month', null)),
                                TextInput::make('year')
                                    ->label('Year')
                                    ->required()
                                    ->numeric()
                                    ->minValue(2000)
                                    ->maxValue(2100),
                                Select::make('month')
                                    ->label('Month')
                                    ->options([
                                        1 => 'January', 2 => 'February', 3 => 'March',
                                        4 => 'April', 5 => 'May', 6 => 'June',
                                        7 => 'July', 8 => 'August', 9 => 'September',
                                        10 => 'October', 11 => 'November', 12 => 'December',
                                    ])
                                    ->nullable()
                                    ->visible(fn (Get $get): bool => $get('type') === KpiPeriodType::MONTHLY->value)
                                    ->requiredIf('type', KpiPeriodType::MONTHLY->value)
                                    ->rules([
                                        fn (Get $get): \Closure => static function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                            if ($get('type') === KpiPeriodType::YEARLY->value && $value !== null) {
                                                $fail('Yearly periods must not have a month.');
                                            }
                                        },
                                    ]),
                            ]),
                    ]),
                Section::make('Date Range')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('starts_at')
                                    ->label('Starts At')
                                    ->required()
                                    ->native(false),
                                DatePicker::make('ends_at')
                                    ->label('Ends At')
                                    ->required()
                                    ->native(false)
                                    ->afterOrEqual('starts_at'),
                            ]),
                    ]),
                Section::make('Status')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }
}
