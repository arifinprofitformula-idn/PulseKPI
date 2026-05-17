<?php

namespace App\Filament\Resources\KpiAssignments\Actions;

use App\Actions\KpiAssignments\BulkAssignKpiTemplateAction;
use App\Models\Department;
use App\Models\Division;
use App\Models\KpiPeriod;
use App\Models\KpiTemplate;
use App\Models\Position;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;

class BulkAssignKpiAction
{
    public static function make(): Action
    {
        return Action::make('bulkAssign')
            ->label('Bulk Assign')
            ->icon('heroicon-o-user-group')
            ->color('warning')
            ->schema([
                Section::make('Assignment Target')
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
                            ]),
                    ]),
                Section::make('Employee Filters')
                    ->description('Filter employees by organizational unit. Leave empty to assign to all eligible employees.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('division_id')
                                    ->label('Division')
                                    ->options(fn (): array => Division::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                                Select::make('department_id')
                                    ->label('Department')
                                    ->options(fn (): array => Department::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                                Select::make('position_id')
                                    ->label('Position')
                                    ->options(fn (): array => Position::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
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
            ])
            ->action(function (array $data): void {
                $action = app(BulkAssignKpiTemplateAction::class);

                $summary = $action->execute($data, Auth::user());

                Notification::make()
                    ->title('Bulk Assignment Complete')
                    ->body(
                        "Total candidates: {$summary['total_candidates']}\n".
                        "Assigned: {$summary['assigned_count']}\n".
                        "Skipped (duplicate): {$summary['skipped_duplicate_count']}\n".
                        "Skipped (invalid): {$summary['skipped_invalid_count']}"
                    )
                    ->success()
                    ->send();
            })
            ->modalHeading('Bulk Assign KPI Template')
            ->modalSubmitActionLabel('Assign');
    }
}
