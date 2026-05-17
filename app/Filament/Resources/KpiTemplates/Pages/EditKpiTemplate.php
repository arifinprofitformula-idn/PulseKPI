<?php

namespace App\Filament\Resources\KpiTemplates\Pages;

use App\Filament\Resources\KpiTemplates\Actions\KpiTemplatePageActions;
use App\Filament\Resources\KpiTemplates\KpiTemplateResource;
use Filament\Resources\Pages\EditRecord;

class EditKpiTemplate extends EditRecord
{
    protected static string $resource = KpiTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            KpiTemplatePageActions::publish(),
            KpiTemplatePageActions::duplicateToYear(),
            KpiTemplatePageActions::duplicateToRevision(),
            KpiTemplatePageActions::toggleActiveState(),
            KpiTemplatePageActions::delete(),
        ];
    }
}
