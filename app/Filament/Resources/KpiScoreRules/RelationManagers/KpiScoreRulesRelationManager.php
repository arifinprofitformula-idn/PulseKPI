<?php

namespace App\Filament\Resources\KpiScoreRules\RelationManagers;

use App\Filament\Resources\KpiScoreRules\Schemas\KpiScoreRuleForm;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KpiScoreRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'scoreRules';

    public function form(Schema $schema): Schema
    {
        return KpiScoreRuleForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                TextColumn::make('score')
                    ->sortable(),
                TextColumn::make('label')
                    ->searchable(),
                TextColumn::make('min_value')
                    ->placeholder('-'),
                TextColumn::make('max_value')
                    ->placeholder('-'),
            ])
            ->defaultSort('score')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
