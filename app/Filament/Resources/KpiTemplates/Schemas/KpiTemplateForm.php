<?php

namespace App\Filament\Resources\KpiTemplates\Schemas;

use App\Models\Department;
use App\Models\Division;
use App\Models\Position;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class KpiTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('code')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                TextInput::make('year')
                                    ->required()
                                    ->numeric()
                                    ->minValue(2000)
                                    ->maxValue(2100),
                                TextInput::make('revision')
                                    ->required()
                                    ->default('00')
                                    ->maxLength(20),
                                Select::make('division_id')
                                    ->label('Division')
                                    ->options(fn (): array => Division::query()->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set): void {
                                        $set('department_id', null);
                                        $set('position_id', null);
                                    }),
                                Select::make('department_id')
                                    ->label('Department')
                                    ->options(fn (Get $get): array => Department::query()
                                        ->when($get('division_id'), fn ($query, $divisionId) => $query->where('division_id', $divisionId))
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('position_id', null))
                                    ->rule(static function (Get $get): Closure {
                                        return static function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                            if (blank($value)) {
                                                return;
                                            }

                                            $exists = Department::query()
                                                ->whereKey($value)
                                                ->when($get('division_id'), fn ($query, $divisionId) => $query->where('division_id', $divisionId))
                                                ->exists();

                                            if (! $exists) {
                                                $fail('The selected department must belong to the selected division.');
                                            }
                                        };
                                    }),
                                Select::make('position_id')
                                    ->label('Position')
                                    ->options(fn (Get $get): array => Position::query()
                                        ->when($get('department_id'), fn ($query, $departmentId) => $query->where('department_id', $departmentId))
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->rule(static function (Get $get): Closure {
                                        return static function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                            if (blank($value)) {
                                                return;
                                            }

                                            $exists = Position::query()
                                                ->whereKey($value)
                                                ->when($get('department_id'), fn ($query, $departmentId) => $query->where('department_id', $departmentId))
                                                ->exists();

                                            if (! $exists) {
                                                $fail('The selected position must belong to the selected department.');
                                            }
                                        };
                                    }),
                                Toggle::make('is_active')
                                    ->required()
                                    ->default(true)
                                    ->inline(false),
                                Textarea::make('description')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
