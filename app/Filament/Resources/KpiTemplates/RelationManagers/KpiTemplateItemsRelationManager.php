<?php

namespace App\Filament\Resources\KpiTemplates\RelationManagers;

use App\Actions\KpiTemplates\ValidateKpiTemplateWeightAction;
use App\Filament\Resources\KpiTemplateItems\Schemas\KpiTemplateItemForm;
use App\Models\KpiTemplateItem;
use App\Policies\KpiTemplateItemPolicy;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KpiTemplateItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'KPI Components';

    public function form(Schema $schema): Schema
    {
        return KpiTemplateItemForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('sort_order')
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('weight')
                    ->label('Weight')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                IconColumn::make('is_required')
                    ->label('Required')
                    ->boolean(),
                TextColumn::make('score_rules_count')
                    ->label('Score Rules')
                    ->counts('scoreRules'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->headerActions([
                CreateAction::make()
                    ->label('Add Component')
                    ->disabled(fn (): bool => $this->getOwnerRecord()->published_at !== null)
                    ->authorize(fn (): bool => app(KpiTemplateItemPolicy::class)->createForTemplate(auth()->user(), $this->getOwnerRecord()))
                    ->using(function (array $data, ValidateKpiTemplateWeightAction $validateWeight): KpiTemplateItem {
                        $template = $this->getOwnerRecord();

                        $validateWeight->ensureDoesNotExceedOneHundred($template, (string) $data['weight']);

                        return $template->items()->create($data);
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit Component')
                    ->disabled(fn (KpiTemplateItem $record): bool => $record->template->published_at !== null)
                    ->using(function (KpiTemplateItem $record, array $data, ValidateKpiTemplateWeightAction $validateWeight): KpiTemplateItem {
                        $validateWeight->ensureDoesNotExceedOneHundred($record->template, (string) $data['weight'], $record);

                        $record->update($data);

                        return $record;
                    }),
                DeleteAction::make()
                    ->disabled(fn (KpiTemplateItem $record): bool => $record->template->published_at !== null),
            ]);
    }
}
