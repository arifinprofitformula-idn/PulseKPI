<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\SystemRole;
use App\Models\Department;
use App\Models\Division;
use App\Models\Position;
use App\Models\User;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('employee_code')
                                    ->label('Employee Code')
                                    ->maxLength(50)
                                    ->unique(User::class, 'employee_code', ignoreRecord: true),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(User::class, 'email', ignoreRecord: true),
                                TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (string $operation): bool => $operation === 'create')
                                    ->dehydrated(fn (?string $state): bool => filled($state))
                                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                                    ->minLength(8)
                                    ->confirmed(),
                                TextInput::make('password_confirmation')
                                    ->label('Confirm Password')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (string $operation): bool => $operation === 'create')
                                    ->dehydrated(false),
                                Select::make('roles')
                                    ->label('Role')
                                    ->multiple()
                                    ->relationship('roles', 'name', fn ($query) => $query->whereNotIn('name', [SystemRole::SUPER_ADMIN->value]))
                                    ->preload(),
                            ]),
                    ]),
                Section::make('Organization')
                    ->schema([
                        Grid::make(2)
                            ->schema([
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
                                Select::make('supervisor_id')
                                    ->label('Supervisor')
                                    ->options(fn (Get $get, ?User $record): array => User::query()
                                        ->whereHas('roles', fn ($query) => $query->whereIn('name', [
                                            SystemRole::MANAGER->value,
                                            SystemRole::SUPERVISOR->value,
                                        ]))
                                        ->when($record, fn ($query, $user) => $query->whereKeyNot($user->getKey()))
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ]),
                Section::make('Contact & Employment')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('whatsapp')
                                    ->label('WhatsApp')
                                    ->tel()
                                    ->maxLength(30),
                                TextInput::make('phone')
                                    ->label('Phone')
                                    ->tel()
                                    ->maxLength(30),
                                Select::make('employment_status')
                                    ->label('Employment Status')
                                    ->options([
                                        'active' => 'Active',
                                        'probation' => 'Probation',
                                        'contract' => 'Contract',
                                        'inactive' => 'Inactive',
                                        'terminated' => 'Terminated',
                                        'resigned' => 'Resigned',
                                        'former' => 'Former',
                                    ]),
                                DatePicker::make('joined_at')
                                    ->label('Date Joined'),
                            ]),
                    ]),
            ]);
    }
}
