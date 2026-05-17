<?php

namespace App\Filament\Resources\KpiTemplates\Actions;

use App\Actions\KpiTemplates\DuplicateKpiTemplateAction;
use App\Actions\KpiTemplates\PublishKpiTemplateAction;
use App\Filament\Resources\KpiTemplates\KpiTemplateResource;
use App\Models\KpiTemplate;
use App\Services\Audit\ActivityLogService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class KpiTemplatePageActions
{
    public static function publish(): Action
    {
        return Action::make('publish')
            ->label('Publish')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Publish KPI Template')
            ->modalDescription('Once published, this template cannot be edited. Ensure all KPI components and scoring rules are complete before continuing.')
            ->authorize(fn (KpiTemplate $record): bool => auth()->user()?->can('publish', $record) ?? false)
            ->action(function (KpiTemplate $record, Action $action): void {
                try {
                    app(PublishKpiTemplateAction::class)->execute($record);
                } catch (ValidationException $e) {
                    $message = collect($e->errors())->flatten()->first()
                        ?? 'The template could not be published.';

                    Notification::make()
                        ->title('Publish failed')
                        ->body($message)
                        ->danger()
                        ->send();

                    $action->halt();

                    return;
                }

                Notification::make()
                    ->title('Template published')
                    ->body('The KPI template is now published and locked for editing.')
                    ->success()
                    ->send();
            })
            ->successRedirectUrl(fn (KpiTemplate $record): string => KpiTemplateResource::getUrl('view', ['record' => $record]));
    }

    public static function duplicateToYear(): Action
    {
        return Action::make('duplicateToYear')
            ->label('Duplicate to New Year')
            ->authorize(fn (KpiTemplate $record): bool => auth()->user()?->can('duplicate', $record) ?? false)
            ->form([
                TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(KpiTemplate::class, 'code'),
                TextInput::make('year')
                    ->required()
                    ->numeric()
                    ->minValue(2000)
                    ->maxValue(2100),
            ])
            ->action(function (KpiTemplate $record, array $data, DuplicateKpiTemplateAction $action): void {
                $action->execute($record, [
                    'code' => $data['code'],
                    'year' => (int) $data['year'],
                    'revision' => '00',
                ]);
            });
    }

    public static function duplicateToRevision(): Action
    {
        return Action::make('duplicateToRevision')
            ->label('Duplicate Revision')
            ->authorize(fn (KpiTemplate $record): bool => auth()->user()?->can('duplicate', $record) ?? false)
            ->form([
                TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(KpiTemplate::class, 'code'),
                TextInput::make('revision')
                    ->required()
                    ->maxLength(20),
            ])
            ->action(function (KpiTemplate $record, array $data, DuplicateKpiTemplateAction $action): void {
                $action->execute($record, [
                    'code' => $data['code'],
                    'revision' => $data['revision'],
                ]);
            });
    }

    public static function toggleActiveState(): Action
    {
        return Action::make('toggleActiveState')
            ->label(fn (KpiTemplate $record): string => $record->is_active ? 'Deactivate' : 'Activate')
            ->color(fn (KpiTemplate $record): string => $record->is_active ? 'danger' : 'warning')
            ->authorize(fn (KpiTemplate $record): bool => auth()->user()?->can('setActiveState', $record) ?? false)
            ->requiresConfirmation()
            ->action(function (KpiTemplate $record, ActivityLogService $activityLogService): void {
                $record->update([
                    'is_active' => ! $record->is_active,
                ]);

                $record->refresh();

                $activityLogService->log(
                    $record->is_active ? 'kpi_template.activated' : 'kpi_template.deactivated',
                    $record,
                    [
                        'template_id' => $record->getKey(),
                        'new' => [
                            'is_active' => $record->is_active,
                        ],
                    ],
                );
            });
    }

    public static function delete(): DeleteAction
    {
        return DeleteAction::make();
    }
}
