<?php

namespace App\Services\Audit;

use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(string $event, Model $subject, array $properties = []): void
    {
        $activity = activity('kpi_template_engine')
            ->performedOn($subject)
            ->event($event)
            ->withProperties($properties);

        if (auth()->check()) {
            $activity->causedBy(auth()->user());
        }

        $activity->log($event);
    }
}
