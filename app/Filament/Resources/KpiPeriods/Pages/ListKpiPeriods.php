<?php

namespace App\Filament\Resources\KpiPeriods\Pages;

use App\Filament\Resources\KpiPeriods\KpiPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKpiPeriods extends ListRecords
{
    protected static string $resource = KpiPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
