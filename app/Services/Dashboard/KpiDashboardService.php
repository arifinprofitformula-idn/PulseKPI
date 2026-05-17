<?php

namespace App\Services\Dashboard;

use App\Actions\Reports\BuildKpiAssessmentReportQuery;
use App\Actions\Reports\BuildKpiAssignmentReportQuery;
use App\Enums\KpiAssessmentStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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

                $statusCounts = [
                    KpiAssessmentStatus::DRAFT->value => $this->forCount($assessmentQuery)->where('status', KpiAssessmentStatus::DRAFT->value)->count(),
                    KpiAssessmentStatus::SUBMITTED->value => $this->forCount($assessmentQuery)->where('status', KpiAssessmentStatus::SUBMITTED->value)->count(),
                    KpiAssessmentStatus::REVIEWED->value => $this->forCount($assessmentQuery)->where('status', KpiAssessmentStatus::REVIEWED->value)->count(),
                    KpiAssessmentStatus::APPROVED->value => $this->forCount($assessmentQuery)->where('status', KpiAssessmentStatus::APPROVED->value)->count(),
                    KpiAssessmentStatus::LOCKED->value => $this->forCount($assessmentQuery)->where('status', KpiAssessmentStatus::LOCKED->value)->count(),
                    KpiAssessmentStatus::REJECTED->value => $this->forCount($assessmentQuery)->where('status', KpiAssessmentStatus::REJECTED->value)->count(),
                ];

                $gradeDistribution = [];

                foreach (self::GRADES as $grade) {
                    $gradeDistribution[$grade] = $this->forCount($assessmentQuery)->where('grade', $grade)->count();
                }

                return [
                    'total_assignments' => (clone $assignmentQuery)->count(),
                    'total_assessments' => array_sum($statusCounts),
                    'draft_assessments' => $statusCounts[KpiAssessmentStatus::DRAFT->value],
                    'submitted_assessments' => $statusCounts[KpiAssessmentStatus::SUBMITTED->value],
                    'reviewed_assessments' => $statusCounts[KpiAssessmentStatus::REVIEWED->value],
                    'approved_assessments' => $statusCounts[KpiAssessmentStatus::APPROVED->value],
                    'locked_assessments' => $statusCounts[KpiAssessmentStatus::LOCKED->value],
                    'rejected_assessments' => $statusCounts[KpiAssessmentStatus::REJECTED->value],
                    'pending_hrd_review' => $statusCounts[KpiAssessmentStatus::SUBMITTED->value],
                    'pending_approver_approval' => $statusCounts[KpiAssessmentStatus::REVIEWED->value],
                    'average_final_score' => round((float) ((clone $assessmentQuery)->avg('final_score') ?? 0), 2),
                    'grade_distribution' => $gradeDistribution,
                ];
            }
        );
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
