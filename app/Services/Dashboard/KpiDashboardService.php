<?php

namespace App\Services\Dashboard;

use App\Actions\Reports\BuildKpiAssessmentReportQuery;
use App\Actions\Reports\BuildKpiAssignmentReportQuery;
use App\Enums\KpiAssessmentStatus;
use App\Models\KpiAssessment;
use App\Models\KpiPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class KpiDashboardService
{
    /**
     * @var list<string>
     */
    public const GRADES = [
        'Excellent',
        'Good',
        'Fair',
        'Needs Improvement',
    ];

    public function __construct(
        private readonly BuildKpiAssignmentReportQuery $buildAssignmentQuery,
        private readonly BuildKpiAssessmentReportQuery $buildReportQuery,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getStats(User $user, array $filters = []): array
    {
        return Cache::remember(
            $this->makeCacheKey($user, $filters),
            now()->addMinutes(5),
            function () use ($user, $filters): array {
                $assignmentQuery = $this->buildAssignmentQuery->execute($user, $filters);
                $assessmentQuery = $this->buildReportQuery->execute($user, $filters);

                $statusCounts = collect((clone $assessmentQuery)
                    ->selectRaw('status, COUNT(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status'))
                    ->mapWithKeys(fn (mixed $total, mixed $status): array => [(string) $status => (int) $total]);

                $gradeCounts = collect((clone $assessmentQuery)
                    ->whereNotNull('grade')
                    ->selectRaw('grade, COUNT(*) as total')
                    ->groupBy('grade')
                    ->pluck('total', 'grade'))
                    ->mapWithKeys(fn (mixed $total, mixed $grade): array => [(string) $grade => (int) $total]);

                $gradeDistribution = collect(self::GRADES)
                    ->mapWithKeys(fn (string $grade): array => [$grade => $gradeCounts->get($grade, 0)])
                    ->all();

                return [
                    'total_assignments' => (clone $assignmentQuery)->count(),
                    'total_assessments' => $statusCounts->sum(),
                    'draft_assessments' => $statusCounts->get(KpiAssessmentStatus::DRAFT->value, 0),
                    'submitted_assessments' => $statusCounts->get(KpiAssessmentStatus::SUBMITTED->value, 0),
                    'reviewed_assessments' => $statusCounts->get(KpiAssessmentStatus::REVIEWED->value, 0),
                    'approved_assessments' => $statusCounts->get(KpiAssessmentStatus::APPROVED->value, 0),
                    'locked_assessments' => $statusCounts->get(KpiAssessmentStatus::LOCKED->value, 0),
                    'rejected_assessments' => $statusCounts->get(KpiAssessmentStatus::REJECTED->value, 0),
                    'pending_hrd_review' => $statusCounts->get(KpiAssessmentStatus::SUBMITTED->value, 0),
                    'pending_approver_approval' => $statusCounts->get(KpiAssessmentStatus::REVIEWED->value, 0),
                    'average_final_score' => round((float) ((clone $assessmentQuery)->avg('final_score') ?? 0), 2),
                    'grade_distribution' => $gradeDistribution,
                ];
            }
        );
    }

    public function getActivePeriodLabel(): ?string
    {
        return KpiPeriod::query()
            ->active()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->value('name');
    }

    /**
     * @return Builder<KpiAssessment>
     */
    public function actionQueueQuery(User $user, int $limit = 5): Builder
    {
        return KpiAssessment::query()
            ->visibleToUser($user)
            ->with([
                'employee',
                'assignment.period',
                'assignment.template',
                'assessor',
            ])
            ->whereIn('status', [
                KpiAssessmentStatus::SUBMITTED->value,
                KpiAssessmentStatus::REVIEWED->value,
            ])
            ->orderByRaw('case when status = ? then 0 else 1 end', [
                KpiAssessmentStatus::SUBMITTED->value,
            ])
            ->latest('updated_at')
            ->limit($limit);
    }

    /**
     * @return Builder<KpiAssessment>
     */
    public function recentAssessmentQuery(User $user, int $limit = 5): Builder
    {
        return KpiAssessment::query()
            ->visibleToUser($user)
            ->with([
                'employee',
                'assignment.period',
                'assignment.template',
                'assessor',
            ])
            ->latest('updated_at')
            ->limit($limit);
    }

    /**
     * @return Collection<int, array{label: string, count: int, percent: int}>
     */
    public function getStatusDistribution(User $user, array $filters = []): Collection
    {
        $stats = $this->getStats($user, $filters);
        $total = max(1, (int) $stats['total_assessments']);

        return collect([
            ['label' => 'Draft', 'count' => (int) $stats['draft_assessments']],
            ['label' => 'Submitted', 'count' => (int) $stats['submitted_assessments']],
            ['label' => 'Reviewed', 'count' => (int) $stats['reviewed_assessments']],
            ['label' => 'Approved', 'count' => (int) $stats['approved_assessments']],
            ['label' => 'Locked', 'count' => (int) $stats['locked_assessments']],
            ['label' => 'Rejected', 'count' => (int) $stats['rejected_assessments']],
        ])->map(fn (array $row): array => [
            'label' => $row['label'],
            'count' => $row['count'],
            'percent' => (int) round(($row['count'] / $total) * 100),
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function makeCacheKey(User $user, array $filters = []): string
    {
        ksort($filters);

        return sprintf(
            'kpi-dashboard:%s:%s:%s',
            $user->getKey(),
            md5(json_encode($user->getRoleNames()->sort()->values()->all(), JSON_THROW_ON_ERROR)),
            md5(json_encode($filters, JSON_THROW_ON_ERROR))
        );
    }

    protected function forCount(Builder $query): Builder
    {
        return clone $query;
    }
}
