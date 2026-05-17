<?php

namespace App\Filament\Resources\KpiAssessmentReports\Pages;

use App\Actions\Reports\RequestKpiAssessmentReportExportAction;
use App\Filament\Resources\KpiAssessmentReports\KpiAssessmentReportResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListKpiAssessmentReports extends ListRecords
{
    protected static string $resource = KpiAssessmentReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function (): void {
                    $filters = $this->tableFilters['report_filters'] ?? [];

                    app(RequestKpiAssessmentReportExportAction::class)->execute(
                        auth()->user(),
                        is_array($filters) ? $filters : []
                    );

                    Notification::make()
                        ->success()
                        ->title('KPI report export is being processed.')
                        ->send();
                }),
        ];
    }
}
