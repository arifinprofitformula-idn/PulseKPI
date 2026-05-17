<?php

namespace App\Observers;

use App\Models\KpiTemplateItem;
use App\Services\Audit\ActivityLogService;
use Illuminate\Validation\ValidationException;

class KpiTemplateItemObserver
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function creating(KpiTemplateItem $item): void
    {
        if ($item->template->published_at !== null) {
            throw ValidationException::withMessages([
                'item' => 'Published KPI templates cannot be modified.',
            ]);
        }
    }

    public function created(KpiTemplateItem $item): void
    {
        $this->activityLogService->log('kpi_template_item.created', $item, [
            'template_id' => $item->kpi_template_id,
            'new' => $item->only([
                'sort_order',
                'name',
                'description',
                'weight',
                'target_description',
                'data_source',
                'is_required',
            ]),
        ]);
    }

    public function updating(KpiTemplateItem $item): void
    {
        if ($item->template->published_at !== null) {
            throw ValidationException::withMessages([
                'item' => 'Published KPI templates cannot be modified.',
            ]);
        }
    }

    public function updated(KpiTemplateItem $item): void
    {
        $changes = $item->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $old = [];

        foreach (array_keys($changes) as $attribute) {
            $old[$attribute] = $item->getOriginal($attribute);
        }

        $this->activityLogService->log('kpi_template_item.updated', $item, [
            'template_id' => $item->kpi_template_id,
            'old' => $old,
            'new' => $changes,
        ]);
    }

    public function deleting(KpiTemplateItem $item): void
    {
        if ($item->template->published_at !== null) {
            throw ValidationException::withMessages([
                'item' => 'Published KPI templates cannot be modified.',
            ]);
        }
    }

    public function deleted(KpiTemplateItem $item): void
    {
        $this->activityLogService->log('kpi_template_item.deleted', $item, [
            'template_id' => $item->kpi_template_id,
            'old' => $item->only([
                'sort_order',
                'name',
                'description',
                'weight',
                'target_description',
                'data_source',
                'is_required',
            ]),
        ]);
    }
}
