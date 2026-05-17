<?php

namespace App\Filament\Resources\KpiPeriods\Pages;

use App\Filament\Resources\KpiPeriods\KpiPeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKpiPeriod extends EditRecord
{
    protected static string $resource = KpiPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
