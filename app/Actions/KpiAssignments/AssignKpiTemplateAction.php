<?php

namespace App\Actions\KpiAssignments;

use App\Enums\KpiAssignmentStatus;
use App\Events\KpiAssigned;
use App\Models\KpiAssignment;
use App\Models\KpiPeriod;
use App\Models\KpiTemplate;
use App\Models\User;
use App\Services\Audit\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AssignKpiTemplateAction
{
    public function __construct(
        private readonly ValidateKpiAssignmentAction $validateAssignment,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function execute(
        KpiPeriod|int $period,
        KpiTemplate|int $template,
        User|int $employee,
        ?User $actor = null,
        ?string $notes = null,
    ): KpiAssignment {
        $authorizer = $actor ?? Auth::user();

        if ($authorizer !== null) {
            Gate::forUser($authorizer)->authorize('create', KpiAssignment::class);
        }

        $resolved = $this->validateAssignment->execute($period, $template, $employee);

        try {
            $assignment = DB::transaction(function () use ($resolved, $authorizer, $notes): KpiAssignment {
                $assignment = KpiAssignment::query()->create([
                    'kpi_period_id' => $resolved['period']->getKey(),
                    'kpi_template_id' => $resolved['template']->getKey(),
                    'employee_id' => $resolved['employee']->getKey(),
                    'assigned_by' => $authorizer?->getKey(),
                    'status' => KpiAssignmentStatus::ASSIGNED,
                    'assigned_at' => now(),
                    'notes' => $notes,
                ]);

                $properties = [
                    'period_id' => $assignment->kpi_period_id,
                    'template_id' => $assignment->kpi_template_id,
                    'employee_id' => $assignment->employee_id,
                    'assigned_by' => $assignment->assigned_by,
                    'status' => $this->normalizeStatus($assignment)->value,
                ];

                $this->activityLogService->log('kpi_assignment.created', $assignment, $properties);
                $this->activityLogService->log('kpi_assignment.assigned', $assignment, $properties + [
                    'assigned_at' => $this->normalizeTimestamp($assignment->assigned_at),
                ]);

                return $assignment;
            });
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'employee_id' => 'The selected employee already has an assignment for this period.',
            ]);
        }

        $assignment->load(['period', 'template', 'employee', 'assignedBy']);

        event(new KpiAssigned($assignment));

        return $assignment;
    }

    private function normalizeStatus(KpiAssignment $assignment): KpiAssignmentStatus
    {
        $status = $assignment->status;

        if ($status instanceof KpiAssignmentStatus) {
            return $status;
        }

        return KpiAssignmentStatus::from($status);
    }

    private function normalizeTimestamp(mixed $value): ?string
    {
        return filled($value) ? Carbon::parse($value)->toIso8601String() : null;
    }
}
