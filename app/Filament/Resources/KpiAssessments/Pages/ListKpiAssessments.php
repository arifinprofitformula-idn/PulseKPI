<?php

namespace App\Filament\Resources\KpiAssessments\Pages;

use App\Actions\KpiAssessments\CreateKpiAssessmentAction;
use App\Filament\Resources\KpiAssessments\KpiAssessmentResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;

class ListKpiAssessments extends ListRecords
{
    protected static string $resource = KpiAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createAssessment')
                ->label('Create Assessment')
                ->icon('heroicon-o-plus')
                ->visible(fn (): bool => static::getResource()::canCreate())
                ->schema([
                    Select::make('kpi_assignment_id')
                        ->label('Assigned KPI')
                        ->options(fn (): array => KpiAssessmentResource::eligibleAssignmentOptions(auth()->user()))
                        ->searchable()
                        ->preload()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $assessment = app(CreateKpiAssessmentAction::class)->execute((int) $data['kpi_assignment_id'], auth()->user());

                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $assessment]));
                }),
        ];
    }
}
