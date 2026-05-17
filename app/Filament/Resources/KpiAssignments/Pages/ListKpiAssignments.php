<?php

namespace App\Filament\Resources\KpiAssignments\Pages;

use App\Filament\Resources\KpiAssignments\Actions\BulkAssignKpiAction;
use App\Filament\Resources\KpiAssignments\KpiAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKpiAssignments extends ListRecords
{
    protected static string $resource = KpiAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BulkAssignKpiAction::make(),
            CreateAction::make()->label('Assign Single'),
        ];
    }
}
