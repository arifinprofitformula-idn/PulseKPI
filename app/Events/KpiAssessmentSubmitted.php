<?php

namespace App\Events;

use App\Models\KpiAssessment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KpiAssessmentSubmitted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public KpiAssessment $assessment) {}
}
