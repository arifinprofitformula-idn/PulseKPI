<?php

namespace App\Filament\Resources\KpiAssignments\Pages;

use App\Actions\KpiAssignments\CancelKpiAssignmentAction;
use App\Enums\KpiAssignmentStatus;
use App\Filament\Resources\KpiAssignments\KpiAssignmentResource;
use App\Models\KpiAssignment;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewKpiAssignment extends ViewRecord
{
    protected static string $resource = KpiAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancel')
                ->label('Cancel Assignment')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->modalHeading('Cancel KPI Assignment')
                ->modalDescription('Are you sure you want to cancel this KPI assignment? This action cannot be undone.')
                ->visible(fn (): bool => $this->resolveStatus() !== KpiAssignmentStatus::CANCELLED)
                ->action(function (): void {
                    /** @var KpiAssignment $record */
                    $record = $this->getRecord();

                    $action = app(CancelKpiAssignmentAction::class);
                    $action->execute($record, Auth::user());

                    $this->refreshFormData(['status', 'cancelled_at']);

                    Notification::make()
                        ->title('KPI assignment cancelled successfully.')
                        ->success()
                        ->send();
                }),
        ];
    }

    private function resolveStatus(): KpiAssignmentStatus
    {
        /** @var KpiAssignment $record */
        $record = $this->getRecord();
        $status = $record->status;

        if ($status instanceof KpiAssignmentStatus) {
            return $status;
        }

        return KpiAssignmentStatus::from((string) $status);
    }
}
