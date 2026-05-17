<?php

namespace App\Services\Dashboard;

use App\Enums\KpiAssessmentStatus;
use App\Enums\SystemRole;
use App\Models\KpiAssessment;
use App\Models\KpiAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ManagerDashboardService
{
    private const CACHE_TTL_SECONDS = 120;

    /**
     * @return array<string, int|float>
     */
    public function getSummaryMetrics(User $user): array
    {
        return Cache::remember(
            $this->makeCacheKey($user, 'summary'),
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            function () use ($user): array {
                $assessmentQuery = $this->assessmentScope($user);

                return [
                    'team_members' => $this->teamMembersQuery($user)->count(),
                    'assignments_to_assess' => $this->assignmentsToAssessQuery($user)->count(),
                    'draft_assessments' => (clone $assessmentQuery)->where('status', KpiAssessmentStatus::DRAFT->value)->count(),
                    'submitted_assessments' => (clone $assessmentQuery)->where('status', KpiAssessmentStatus::SUBMITTED->value)->count(),
                    'rejected_assessments' => (clone $assessmentQuery)->where('status', KpiAssessmentStatus::REJECTED->value)->count(),
                    'approved_or_locked' => (clone $assessmentQuery)
                        ->whereIn('status', [
                            KpiAssessmentStatus::APPROVED->value,
                            KpiAssessmentStatus::LOCKED->value,
                        ])->count(),
                    'average_team_score' => round((float) ((clone $assessmentQuery)->whereNotNull('final_score')->avg('final_score') ?? 0), 2),
                ];
            }
        );
    }

    /**
     * @return Builder<KpiAssignment>
     */
    public function teamOverviewQuery(User $user, int $limit = 6): Builder
    {
        $latestAssignmentIds = KpiAssignment::query()
            ->selectRaw('MAX(kpi_assignments.id)')
            ->whereHas('employee', fn (Builder $builder) => $builder->where('supervisor_id', $user->getKey()))
            ->groupBy('employee_id');

        return KpiAssignment::query()
            ->with([
                'period',
                'template',
                'employee.division',
                'employee.department',
                'employee.position',
                'assessment.assessor',
            ])
            ->whereIn('id', $latestAssignmentIds)
            ->latest('assigned_at')
            ->limit($limit);
    }

    /**
     * @return Builder<KpiAssessment>
     */
    public function assessmentQueueQuery(User $user, int $limit = 6): Builder
    {
        return $this->assessmentScope($user)
            ->with([
                'assignment.period',
                'assignment.template',
                'employee.division',
                'employee.department',
                'assessor',
            ])
            ->whereIn('status', [
                KpiAssessmentStatus::DRAFT->value,
                KpiAssessmentStatus::REJECTED->value,
                KpiAssessmentStatus::SUBMITTED->value,
            ])
            ->orderByRaw('
                case
                    when status = ? then 0
                    when status = ? then 1
                    else 2
                end
            ', [
                KpiAssessmentStatus::REJECTED->value,
                KpiAssessmentStatus::DRAFT->value,
            ])
            ->latest('updated_at')
            ->limit($limit);
    }

    /**
     * @return array{labels: list<string>, values: list<float>}
     */
    public function getPerformanceTrend(User $user): array
    {
        return Cache::remember(
            $this->makeCacheKey($user, 'trend'),
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            function () use ($user): array {
                $points = $this->assessmentScope($user)
                    ->join('kpi_assignments', 'kpi_assignments.id', '=', 'kpi_assessments.kpi_assignment_id')
                    ->join('kpi_periods', 'kpi_periods.id', '=', 'kpi_assignments.kpi_period_id')
                    ->whereNotNull('kpi_assessments.final_score')
                    ->selectRaw('kpi_periods.year, kpi_periods.month, kpi_periods.name, AVG(kpi_assessments.final_score) as average_score')
                    ->groupBy('kpi_periods.year', 'kpi_periods.month', 'kpi_periods.name')
                    ->orderByDesc('kpi_periods.year')
                    ->orderByDesc('kpi_periods.month')
                    ->limit(6)
                    ->get()
                    ->reverse()
                    ->values();

                return [
                    'labels' => $points->pluck('name')->map(fn (mixed $name): string => (string) $name)->all(),
                    'values' => $points->pluck('average_score')->map(fn (mixed $score): float => round((float) $score, 2))->all(),
                ];
            }
        );
    }

    public function makeCacheKey(User $user, string $segment): string
    {
        return sprintf('manager-dashboard:%s:%s', $user->getKey(), $segment);
    }

    /**
     * @return Builder<User>
     */
    protected function teamMembersQuery(User $user): Builder
    {
        return User::query()
            ->role(SystemRole::EMPLOYEE->value)
            ->where('supervisor_id', $user->getKey());
    }

    /**
     * @return Builder<KpiAssignment>
     */
    protected function assignmentsToAssessQuery(User $user): Builder
    {
        return KpiAssignment::query()
            ->where('status', 'assigned')
            ->whereHas('employee', fn (Builder $builder) => $builder->where('supervisor_id', $user->getKey()))
            ->whereDoesntHave('assessment');
    }

    /**
     * @return Builder<KpiAssessment>
     */
    protected function assessmentScope(User $user): Builder
    {
        return KpiAssessment::query()->visibleToUser($user);
    }
}
