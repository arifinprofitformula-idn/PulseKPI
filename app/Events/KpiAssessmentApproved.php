<?php

namespace App\Events;

use App\Models\KpiAssessment;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KpiAssessmentApproved
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly KpiAssessment $assessment,
        public readonly ?User $actor = null,
        public readonly ?string $notes = null,
    ) {}
}
