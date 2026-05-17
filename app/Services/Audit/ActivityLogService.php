<?php

namespace App\Services\Audit;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(string $event, Model $subject, array $properties = [], ?User $actor = null): void
    {
        $activity = activity('pulsekpi')
            ->performedOn($subject)
            ->event($event)
            ->withProperties($properties);

        if ($actor !== null) {
            $activity->causedBy($actor);
        } elseif (auth()->check()) {
            $activity->causedBy(auth()->user());
        }

        $activity->log($event);
    }
}
