<?php

namespace App\Filament\Resources\KpiScoreRules\Schemas;

use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class KpiScoreRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(static::getComponents());
    }

    /**
     * @return array<int, Section>
     */
    public static function getComponents(): array
    {
        return [
            Section::make('Scoring Rule Details')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('score')
                                ->required()
                                ->options([
                                    0 => '0',
                                    1 => '1',
                                    2 => '2',
                                ])
                                ->rule(Rule::in([0, 1, 2]))
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                            TextInput::make('label')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('min_value')
                                ->numeric()
                                ->step('0.01'),
                            TextInput::make('max_value')
                                ->numeric()
                                ->step('0.01')
                                ->rule(static function (Get $get): Closure {
                                    return static function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                        if (blank($value) || blank($get('min_value'))) {
                                            return;
                                        }

                                        if ((float) $value < (float) $get('min_value')) {
                                            $fail('The maximum value must be greater than or equal to the minimum value.');
                                        }
                                    };
                                }),
                            Textarea::make('description')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                ]),
        ];
    }
}
