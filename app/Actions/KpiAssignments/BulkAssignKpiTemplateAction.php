<?php

namespace App\Actions\KpiAssignments;

use App\Enums\KpiAssignmentStatus;
use App\Events\KpiAssigned;
use App\Models\KpiAssignment;
use App\Models\KpiPeriod;
use App\Models\KpiTemplate;
use App\Models\User;
use App\Services\Audit\ActivityLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BulkAssignKpiTemplateAction
{
    public function __construct(
        private readonly ValidateKpiAssignmentAction $validateAssignment,
        private readonly ActivityLogService $activityLogService,
    ) {}

    /**
     * @param  array{
     *     kpi_period_id: int,
     *     kpi_template_id: int,
     *     division_id?: ?int,
     *     department_id?: ?int,
     *     position_id?: ?int,
     *     notes?: ?string
     * }  $data
     * @return array{
     *     total_candidates: int,
     *     assigned_count: int,
     *     skipped_duplicate_count: int,
     *     skipped_invalid_count: int
     * }
     */
    public function execute(array $data, ?User $actor = null): array
    {
        $authorizer = $actor ?? Auth::user();

        if ($authorizer !== null) {
            Gate::forUser($authorizer)->authorize('bulkAssign', KpiAssignment::class);
        }

        $period = KpiPeriod::query()->findOrFail($data['kpi_period_id']);
        $template = KpiTemplate::query()->findOrFail($data['kpi_template_id']);

        $this->validateAssignment->ensurePeriodAndTemplate($period, $template);

        $summary = [
            'total_candidates' => 0,
            'assigned_count' => 0,
            'skipped_duplicate_count' => 0,
            'skipped_invalid_count' => 0,
        ];

        $assignedIds = [];

        DB::transaction(function () use ($data, $authorizer, $period, $template, &$summary, &$assignedIds): void {
            $this->employeeQuery($data, $period)
                ->orderBy('users.id')
                ->chunkById(100, function (Collection $employees) use ($data, $authorizer, $period, $template, &$summary, &$assignedIds): void {
                    $summary['total_candidates'] += $employees->count();

                    $existingEmployeeIds = KpiAssignment::query()
                        ->where('kpi_period_id', $period->getKey())
                        ->whereIn('employee_id', $employees->pluck('id'))
                        ->pluck('employee_id')
                        ->all();

                    foreach ($employees as $employee) {
                        if (! $employee instanceof User) {
                            continue;
                        }

                        if (in_array($employee->getKey(), $existingEmployeeIds, true)) {
                            $summary['skipped_duplicate_count']++;

                            continue;
                        }

                        if (! $this->validateAssignment->isAssignableEmployee($employee, $period)) {
                            $summary['skipped_invalid_count']++;

                            continue;
                        }

                        $assignment = KpiAssignment::query()->create([
                            'kpi_period_id' => $period->getKey(),
                            'kpi_template_id' => $template->getKey(),
                            'employee_id' => $employee->getKey(),
                            'assigned_by' => $authorizer?->getKey(),
                            'status' => KpiAssignmentStatus::ASSIGNED,
                            'assigned_at' => now(),
                            'notes' => $data['notes'] ?? null,
                        ]);

                        $properties = [
                            'period_id' => $assignment->kpi_period_id,
                            'template_id' => $assignment->kpi_template_id,
                            'employee_id' => $assignment->employee_id,
                            'assigned_by' => $assignment->assigned_by,
                            'status' => $assignment->status->value,
                        ];

                        $this->activityLogService->log('kpi_assignment.created', $assignment, $properties);
                        $this->activityLogService->log('kpi_assignment.assigned', $assignment, $properties + [
                            'assigned_at' => $assignment->assigned_at?->toIso8601String(),
                        ]);

                        $summary['assigned_count']++;
                        $assignedIds[] = $assignment->getKey();
                    }
                });

            $this->activityLogService->log('kpi_assignment.bulk_assigned', $period, [
                'period_id' => $period->getKey(),
                'template_id' => $template->getKey(),
                'actor_user_id' => $authorizer?->getKey(),
                'division_id' => $data['division_id'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'position_id' => $data['position_id'] ?? null,
                'summary' => $summary,
            ]);
        });

        if ($assignedIds !== []) {
            KpiAssignment::query()
                ->with(['period', 'employee'])
                ->whereKey($assignedIds)
                ->get()
                ->each(fn (KpiAssignment $assignment) => event(new KpiAssigned($assignment)));
        }

        return $summary;
    }

    /**
     * @param  array{division_id?: ?int, department_id?: ?int, position_id?: ?int}  $filters
     */
    private function employeeQuery(array $filters, KpiPeriod $period): Builder
    {
        return $this->validateAssignment->employeeBaseQuery()
            ->where(function (Builder $query) use ($period): void {
                $query->whereNull('joined_at')
                    ->orWhereDate('joined_at', '<=', $period->ends_at->toDateString());
            })
            ->when($filters['division_id'] ?? null, fn (Builder $query, int $divisionId) => $query->where('division_id', $divisionId))
            ->when($filters['department_id'] ?? null, fn (Builder $query, int $departmentId) => $query->where('department_id', $departmentId))
            ->when($filters['position_id'] ?? null, fn (Builder $query, int $positionId) => $query->where('position_id', $positionId));
    }
}
