<?php

namespace App\Observers;

use App\Actions\KpiTemplates\ValidateKpiScoreRuleAction;
use App\Models\KpiScoreRule;
use App\Services\Audit\ActivityLogService;
use Illuminate\Validation\ValidationException;

class KpiScoreRuleObserver
{
    public function __construct(
        private readonly ValidateKpiScoreRuleAction $validateKpiScoreRuleAction,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function saving(KpiScoreRule $scoreRule): void
    {
        $this->validateKpiScoreRuleAction->execute($scoreRule);
    }

    public function created(KpiScoreRule $scoreRule): void
    {
        $this->activityLogService->log('kpi_score_rule.created', $scoreRule, [
            'template_id' => $scoreRule->item->kpi_template_id,
            'item_id' => $scoreRule->kpi_template_item_id,
            'new' => $scoreRule->only([
                'score',
                'label',
                'min_value',
                'max_value',
                'description',
            ]),
        ]);
    }

    public function updated(KpiScoreRule $scoreRule): void
    {
        $changes = $scoreRule->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $old = [];

        foreach (array_keys($changes) as $attribute) {
            $old[$attribute] = $scoreRule->getOriginal($attribute);
        }

        $this->activityLogService->log('kpi_score_rule.updated', $scoreRule, [
            'template_id' => $scoreRule->item->kpi_template_id,
            'item_id' => $scoreRule->kpi_template_item_id,
            'old' => $old,
            'new' => $changes,
        ]);
    }

    public function deleting(KpiScoreRule $scoreRule): void
    {
        if ($scoreRule->item->template->published_at !== null) {
            throw ValidationException::withMessages([
                'score' => 'Published KPI templates cannot be modified.',
            ]);
        }
    }

    public function deleted(KpiScoreRule $scoreRule): void
    {
        $this->activityLogService->log('kpi_score_rule.deleted', $scoreRule, [
            'template_id' => $scoreRule->item?->kpi_template_id,
            'item_id' => $scoreRule->kpi_template_item_id,
            'old' => $scoreRule->only([
                'score',
                'label',
                'min_value',
                'max_value',
                'description',
            ]),
        ]);
    }
}
