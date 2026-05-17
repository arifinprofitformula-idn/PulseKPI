<?php

namespace App\Filament\Resources\KpiTemplateItems\Pages;

use App\Actions\KpiTemplates\ValidateKpiTemplateWeightAction;
use App\Filament\Resources\KpiTemplateItems\KpiTemplateItemResource;
use Filament\Resources\Pages\EditRecord;

class EditKpiTemplateItem extends EditRecord
{
    protected static string $resource = KpiTemplateItemResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        app(ValidateKpiTemplateWeightAction::class)->ensureDoesNotExceedOneHundred(
            $this->record->template,
            (string) $data['weight'],
            $this->record,
        );

        return $data;
    }
}
