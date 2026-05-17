<?php

namespace App\Actions\KpiTemplates;

use App\Models\KpiScoreRule;
use App\Models\KpiTemplateItem;
use Illuminate\Validation\ValidationException;

class ValidateKpiScoreRuleAction
{
    public function execute(KpiScoreRule $scoreRule): void
    {
        if (! in_array($scoreRule->score, [0, 1, 2], true)) {
            throw ValidationException::withMessages([
                'score' => 'The score must be one of 0, 1, or 2.',
            ]);
        }

        $item = $scoreRule->item()->withoutGlobalScopes()->first();

        if ($item instanceof KpiTemplateItem && $item->template->published_at !== null) {
            throw ValidationException::withMessages([
                'score' => 'Published KPI templates cannot be modified.',
            ]);
        }

        $duplicateExists = KpiScoreRule::query()
            ->where('kpi_template_item_id', $scoreRule->kpi_template_item_id)
            ->where('score', $scoreRule->score)
            ->when($scoreRule->exists, fn ($query) => $query->whereKeyNot($scoreRule->getKey()))
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'score' => 'Each scoring rule score must be unique within the KPI component.',
            ]);
        }
    }
}
