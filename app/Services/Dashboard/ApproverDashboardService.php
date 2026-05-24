<?php

namespace App\Services\Dashboard;

use App\Enums\KpiApprovalAction;
use App\Enums\KpiAssessmentStatus;
use App\Models\KpiApproval;
use App\Models\KpiAssessment;
use App\Models\KpiPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ApproverDashboardService
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
                $decisionQuery = $this->decisionScope($user);
                $gradeCounts = collect((clone $assessmentQuery)
                    ->whereNotNull('grade')
                    ->selectRaw('grade, COUNT(*) as total')
                    ->groupBy('grade')
                    ->pluck('total', 'grade'))
                    ->mapWithKeys(fn (mixed $total, mixed $grade): array => [(string) $grade => (int) $total]);

                return [
                    'total_assessments' => (clone $assessmentQuery)->count(),
                    'pending_approval' => (clone $assessmentQuery)->where('status', KpiAssessmentStatus::REVIEWED->value)->count(),
                    'approved' => (clone $assessmentQuery)->where('status', KpiAssessmentStatus::APPROVED->value)->count(),
                    'rejected' => (clone $decisionQuery)->where('action', KpiApprovalAction::REJECTED->value)->count(),
                    'locked' => (clone $assessmentQuery)->where('status', KpiAssessmentStatus::LOCKED->value)->count(),
                    'average_approved_score' => round((float) ((clone $assessmentQuery)
                        ->where('status', KpiAssessmentStatus::APPROVED->value)
                        ->whereNotNull('final_score')
                        ->avg('final_score') ?? 0), 2),
                    'completed_this_period' => (clone $decisionQuery)
                        ->whereIn('action', [
                            KpiApprovalAction::APPROVED->value,
                            KpiApprovalAction::REJECTED->value,
                            KpiApprovalAction::LOCKED->value,
                        ])
                        ->whereBetween('acted_at', [now()->startOfMonth(), now()->endOfMonth()])
                        ->count(),
                    'excellent_grade' => $gradeCounts->get('Excellent', 0),
                    'good_grade' => $gradeCounts->get('Good', 0),
                    'fair_grade' => $gradeCounts->get('Fair', 0),
                    'needs_improvement_grade' => $gradeCounts->get('Needs Improvement', 0),
                ];
            }
        );
    }

    /**
     * @return Builder<KpiAssessment>
     */
    public function approvalQueueQuery(User $user, int $limit = 6): Builder
    {
        return $this->assessmentScope($user)
            ->with([
                'assignment.period',
                'assignment.template',
                'employee.division',
                'employee.department',
                'assessor',
            ])
            ->where('status', KpiAssessmentStatus::REVIEWED->value)
            ->latest('reviewed_at')
            ->limit($limit);
    }

    /**
     * @return Builder<KpiApproval>
     */
    public function recentDecisionQuery(User $user, int $limit = 6): Builder
    {
        return $this->decisionScope($user)
            ->with([
                'assessment.assignment.period',
                'assessment.employee',
                'assessment.assessor',
            ])
            ->whereIn('action', [
                KpiApprovalAction::APPROVED->value,
                KpiApprovalAction::REJECTED->value,
                KpiApprovalAction::LOCKED->value,
            ])
            ->latest('acted_at')
            ->limit($limit);
    }

    public function getActivePeriodLabel(): ?string
    {
        return KpiPeriod::query()
            ->active()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->value('name');
    }

    public function makeCacheKey(User $user, string $segment): string
    {
        return sprintf('approver-dashboard:%s:%s', $user->getKey(), $segment);
    }

    /**
     * @return Builder<KpiAssessment>
     */
    protected function assessmentScope(User $user): Builder
    {
        return KpiAssessment::query()->visibleToUser($user);
    }

    /**
     * @return Builder<KpiApproval>
     */
    protected function decisionScope(User $user): Builder
    {
        return KpiApproval::query()->where('actor_id', $user->getKey());
    }
}
