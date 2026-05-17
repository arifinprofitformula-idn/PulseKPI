<?php

namespace App\Actions\KpiAssignments;

use App\Enums\KpiAssignmentStatus;
use App\Events\KpiAssignmentCancelled;
use App\Models\KpiAssignment;
use App\Models\User;
use App\Services\Audit\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CancelKpiAssignmentAction
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function execute(
        KpiAssignment $assignment,
        ?User $actor = null,
        ?string $reason = null,
    ): KpiAssignment {
        $authorizer = $actor ?? Auth::user();

        if ($authorizer !== null) {
            Gate::forUser($authorizer)->authorize('cancel', $assignment);
        }

        if ($assignment->status === KpiAssignmentStatus::CANCELLED) {
            throw ValidationException::withMessages([
                'status' => 'This KPI assignment has already been cancelled.',
            ]);
        }

        if ($assignment->assessment()->exists()) {
            throw ValidationException::withMessages([
                'assignment' => 'This KPI assignment cannot be cancelled because an assessment already exists for it.',
            ]);
        }

        $cancelledAt = now();

        $assignment = DB::transaction(function () use ($assignment, $authorizer, $reason, $cancelledAt): KpiAssignment {
            $assignment->update([
                'status' => KpiAssignmentStatus::CANCELLED,
                'cancelled_at' => $cancelledAt,
                'notes' => $reason ?? $assignment->notes,
            ]);

            $this->activityLogService->log('kpi_assignment.cancelled', $assignment, [
                'period_id' => $assignment->kpi_period_id,
                'template_id' => $assignment->kpi_template_id,
                'employee_id' => $assignment->employee_id,
                'actor_user_id' => $authorizer?->getKey(),
                'cancelled_at' => $cancelledAt->toIso8601String(),
                'reason' => $reason,
            ]);

            return $assignment;
        });

        $assignment->load(['period', 'template', 'employee', 'assignedBy']);

        event(new KpiAssignmentCancelled($assignment));

        return $assignment;
    }
}
