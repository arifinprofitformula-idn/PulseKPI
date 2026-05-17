<?php

namespace App\Filament\Resources\KpiAssessmentReports\Tables;

use App\Actions\Reports\BuildKpiAssessmentReportQuery;
use App\Filament\Resources\KpiAssessmentReports\KpiAssessmentReportResource;
use App\Services\Dashboard\KpiDashboardService;
use App\Support\KpiStatusBadge;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KpiAssessmentReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('assignment.period.name')
                    ->label('Period')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.division.name')
                    ->label('Division')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('employee.department.name')
                    ->label('Department')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('employee.position.name')
                    ->label('Position')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('assessor.name')
                    ->label('Assessor')
                    ->toggleable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('assignment.template.name')
                    ->label('Template')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => KpiStatusBadge::assessmentLabel($state))
                    ->color(fn (mixed $state): string => KpiStatusBadge::assessmentColor($state)),
                TextColumn::make('kpi_score')
                    ->label('KPI Score')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('attendance_deduction')
                    ->label('Attendance Deduction')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('final_score')
                    ->label('Final Score')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('grade')
                    ->label('Grade')
                    ->badge()
                    ->sortable(),
                TextColumn::make('submitted_at')
                    ->label('Submitted At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('reviewed_at')
                    ->label('Reviewed At')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('approved_at')
                    ->label('Approved At')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('locked_at')
                    ->label('Locked At')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                Filter::make('report_filters')
                    ->form([
                        Select::make('period_id')
                            ->label('Period')
                            ->options(fn (): array => KpiAssessmentReportResource::periodOptions(auth()->user())),
                        Select::make('year')
                            ->label('Year')
                            ->options(fn (): array => KpiAssessmentReportResource::yearOptions(auth()->user())),
                        Select::make('month')
                            ->label('Month')
                            ->options([
                                1 => 'January',
                                2 => 'February',
                                3 => 'March',
                                4 => 'April',
                                5 => 'May',
                                6 => 'June',
                                7 => 'July',
                                8 => 'August',
                                9 => 'September',
                                10 => 'October',
                                11 => 'November',
                                12 => 'December',
                            ]),
                        Select::make('division_id')
                            ->label('Division')
                            ->options(fn (): array => KpiAssessmentReportResource::divisionOptions(auth()->user())),
                        Select::make('department_id')
                            ->label('Department')
                            ->options(fn (): array => KpiAssessmentReportResource::departmentOptions(auth()->user())),
                        Select::make('position_id')
                            ->label('Position')
                            ->options(fn (): array => KpiAssessmentReportResource::positionOptions(auth()->user())),
                        Select::make('assessor_id')
                            ->label('Assessor')
                            ->options(fn (): array => KpiAssessmentReportResource::assessorOptions(auth()->user())),
                        Select::make('status')
                            ->label('Status')
                            ->options(fn (): array => KpiAssessmentReportResource::statusOptions(auth()->user())),
                        Select::make('grade')
                            ->label('Grade')
                            ->options(array_combine(KpiDashboardService::GRADES, KpiDashboardService::GRADES)),
                        TextInput::make('min_final_score')
                            ->label('Min Final Score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),
                        TextInput::make('max_final_score')
                            ->label('Max Final Score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return app(BuildKpiAssessmentReportQuery::class)->applyFilters($query, $data);
                    }),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->defaultSort('submitted_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50])
            ->emptyStateHeading('No KPI assessment reports found')
            ->emptyStateDescription('Adjust the filters to review KPI assessment results within your scope.')
            ->emptyStateIcon('heroicon-o-chart-bar-square');
    }
}
