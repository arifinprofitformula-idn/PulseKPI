<?php

namespace App\Observers;

use App\Models\KpiPeriod;
use App\Services\Audit\ActivityLogService;

class KpiPeriodObserver
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function created(KpiPeriod $period): void
    {
        $this->activityLogService->log('kpi_period.created', $period, [
            'period_id' => $period->getKey(),
            'new' => $period->only([
                'name',
                'type',
                'month',
                'year',
                'starts_at',
                'ends_at',
                'is_active',
            ]),
        ]);
    }

    public function updated(KpiPeriod $period): void
    {
        $changes = $period->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $old = [];

        foreach (array_keys($changes) as $attribute) {
            $old[$attribute] = $period->getOriginal($attribute);
        }

        $this->activityLogService->log('kpi_period.updated', $period, [
            'period_id' => $period->getKey(),
            'old' => $old,
            'new' => $changes,
        ]);
    }

    public function deleted(KpiPeriod $period): void
    {
        $this->activityLogService->log('kpi_period.deleted', $period, [
            'period_id' => $period->getKey(),
            'old' => $period->only([
                'name',
                'type',
                'month',
                'year',
                'starts_at',
                'ends_at',
                'is_active',
            ]),
        ]);
    }
}
