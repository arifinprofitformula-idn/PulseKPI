<?php

namespace App\Observers;

use App\Models\KpiTemplate;
use App\Services\Audit\ActivityLogService;
use Illuminate\Validation\ValidationException;

class KpiTemplateObserver
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function created(KpiTemplate $template): void
    {
        $this->activityLogService->log('kpi_template.created', $template, [
            'template_id' => $template->getKey(),
            'new' => $template->only([
                'name',
                'code',
                'year',
                'division_id',
                'department_id',
                'position_id',
                'revision',
                'description',
                'is_active',
                'published_at',
            ]),
        ]);
    }

    public function updating(KpiTemplate $template): void
    {
        if ($template->getOriginal('published_at') === null) {
            return;
        }

        $dirtyKeys = array_keys($template->getDirty());
        $allowedKeys = ['is_active', 'updated_at'];

        if (array_diff($dirtyKeys, $allowedKeys) !== []) {
            throw ValidationException::withMessages([
                'template' => 'Published KPI templates cannot be modified.',
            ]);
        }
    }

    public function updated(KpiTemplate $template): void
    {
        $changes = $template->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        if (array_keys($changes) === ['published_at'] || array_keys($changes) === ['is_active']) {
            return;
        }

        $old = [];

        foreach (array_keys($changes) as $attribute) {
            $old[$attribute] = $template->getOriginal($attribute);
        }

        $this->activityLogService->log('kpi_template.updated', $template, [
            'template_id' => $template->getKey(),
            'old' => $old,
            'new' => $changes,
        ]);
    }

    public function deleting(KpiTemplate $template): void
    {
        if ($template->published_at !== null) {
            throw ValidationException::withMessages([
                'template' => 'Published KPI templates cannot be deleted. Deactivate them instead.',
            ]);
        }
    }
}
