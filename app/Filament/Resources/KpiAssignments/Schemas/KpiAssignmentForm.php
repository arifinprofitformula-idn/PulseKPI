<?php

namespace App\Filament\Resources\KpiAssignments\Schemas;

use App\Actions\KpiAssignments\ValidateKpiAssignmentAction;
use App\Models\KpiPeriod;
use App\Models\KpiTemplate;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KpiAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Assignment Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('kpi_period_id')
                                    ->label('KPI Period')
                                    ->options(fn (): array => KpiPeriod::query()
                                        ->where('is_active', true)
                                        ->orderByDesc('year')
                                        ->orderByDesc('month')
                                        ->get()
                                        ->mapWithKeys(fn (KpiPeriod $period): array => [$period->getKey() => $period->name])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('kpi_template_id')
                                    ->label('KPI Template')
                                    ->options(fn (): array => KpiTemplate::query()
                                        ->where('is_active', true)
                                        ->whereNotNull('published_at')
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn (KpiTemplate $template): array => [
                                            $template->getKey() => "{$template->name} (Rev. {$template->revision})",
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('employee_id')
                                    ->label('Employee')
                                    ->options(fn (): array => app(ValidateKpiAssignmentAction::class)
                                        ->employeeBaseQuery()
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn (User $user): array => [
                                            $user->getKey() => "{$user->name} ({$user->employee_code})",
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),
                    ]),
                Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }
}
