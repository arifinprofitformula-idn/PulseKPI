<?php

namespace App\Filament\Resources\KpiAssignments\Pages;

use App\Actions\KpiAssignments\AssignKpiTemplateAction;
use App\Filament\Resources\KpiAssignments\KpiAssignmentResource;
use App\Models\KpiAssignment;
use App\Models\KpiPeriod;
use App\Models\KpiTemplate;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateKpiAssignment extends CreateRecord
{
    protected static string $resource = KpiAssignmentResource::class;

    protected function handleRecordCreation(array $data): KpiAssignment
    {
        $action = app(AssignKpiTemplateAction::class);

        return $action->execute(
            period: KpiPeriod::query()->findOrFail($data['kpi_period_id']),
            template: KpiTemplate::query()->findOrFail($data['kpi_template_id']),
            employee: User::query()->findOrFail($data['employee_id']),
            actor: Auth::user(),
            notes: $data['notes'] ?? null,
        );
    }
}
