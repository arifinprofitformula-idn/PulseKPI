<?php

namespace App\Services\Dashboard;

use App\Actions\Reports\BuildKpiAssessmentReportQuery;
use App\Actions\Reports\BuildKpiAssignmentReportQuery;
use App\Enums\KpiAssessmentStatus;
use App\Enums\KpiAssignmentStatus;
use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Models\KpiAssessment;
use App\Models\KpiAssignment;
use App\Models\KpiReportExport;
use App\Models\KpiTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;

class HrdDashboardService
{
    private const CACHE_TTL_SECONDS = 120;

    /**
     * @var array<string, string>
     */
    public const STATUS_COLORS = [
        'draft' => 'gray',
        'assigned' => 'info',
        'submitted' => 'warning',
        'reviewed' => 'indigo',
        'approved' => 'success',
        'rejected' => 'danger',
        'locked' => 'slate',
        'processing' => 'info',
        'completed' => 'success',
        'failed' => 'danger',
    ];

    public function __construct(
        private readonly BuildKpiAssignmentReportQuery $buildAssignmentQuery,
        private readonly BuildKpiAssessmentReportQuery $buildAssessmentQuery,
    ) {}

    public function canAccess(User $user): bool
    {
        return $user->hasRole(SystemRole::SUPER_ADMIN->value)
            || $user->hasRole(SystemRole::HRD->value)
            || $user->can(SystemPermission::VIEW_REPORTS->value);
    }

    /**
     * @return array<string, int>
     */
    public function getSummaryMetrics(User $user): array
    {
        return Cache::remember(
            $this->makeCacheKey($user, 'summary'),
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            function () use ($user): array {
                $assignmentQuery = $this->assignmentQuery($user);
                $assessmentQuery = $this->assessmentQuery($user);

                return [
                    'total_employees' => $this->employeeScopeQuery($user)->count(),
                    'active_templates' => KpiTemplate::query()
                        ->where('is_active', true)
                        ->whereNotNull('published_at')
                        ->count(),
                    'active_assignments' => (clone $assignmentQuery)
                        ->where('status', KpiAssignmentStatus::ASSIGNED->value)
                        ->count(),
                    'pending_review' => $this->countAssessmentsByStatus($assessmentQuery, KpiAssessmentStatus::SUBMITTED),
                    'waiting_approval' => $this->countAssessmentsByStatus($assessmentQuery, KpiAssessmentStatus::REVIEWED),
                    'approved' => $this->countAssessmentsByStatus($assessmentQuery, KpiAssessmentStatus::APPROVED),
                    'locked' => $this->countAssessmentsByStatus($assessmentQuery, KpiAssessmentStatus::LOCKED),
                ];
            }
        );
    }

    /**
     * @return array<string, int|string>
     */
    public function getWorkflowStatus(User $user): array
    {
        return Cache::remember(
            $this->makeCacheKey($user, 'workflow'),
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            function () use ($user): array {
                $assignmentQuery = $this->assignmentQuery($user);
                $assessmentQuery = $this->assessmentQuery($user);

                return [
                    'draft' => $this->countAssessmentsByStatus($assessmentQuery, KpiAssessmentStatus::DRAFT),
                    'assigned' => (clone $assignmentQuery)
                        ->where('status', KpiAssignmentStatus::ASSIGNED->value)
                        ->count(),
                    'submitted' => $this->countAssessmentsByStatus($assessmentQuery, KpiAssessmentStatus::SUBMITTED),
                    'reviewed' => $this->countAssessmentsByStatus($assessmentQuery, KpiAssessmentStatus::REVIEWED),
                    'approved' => $this->countAssessmentsByStatus($assessmentQuery, KpiAssessmentStatus::APPROVED),
                    'rejected' => $this->countAssessmentsByStatus($assessmentQuery, KpiAssessmentStatus::REJECTED),
                    'locked' => $this->countAssessmentsByStatus($assessmentQuery, KpiAssessmentStatus::LOCKED),
                ];
            }
        );
    }

    /**
     * @return array{labels: list<string>, values: list<float>}
     */
    public function getFinalScoreTrend(User $user): array
    {
        return Cache::remember(
            $this->makeCacheKey($user, 'trend'),
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            function () use ($user): array {
                $points = KpiAssessment::query()
                    ->visibleToUser($user)
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

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    public function getGradeDistribution(User $user): array
    {
        return Cache::remember(
            $this->makeCacheKey($user, 'grades'),
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            function () use ($user): array {
                $distribution = KpiAssessment::query()
                    ->visibleToUser($user)
                    ->whereNotNull('grade')
                    ->selectRaw('grade, COUNT(*) as total')
                    ->groupBy('grade')
                    ->orderBy('grade')
                    ->pluck('total', 'grade');

                return [
                    'labels' => $distribution->keys()->map(fn (mixed $grade): string => (string) $grade)->all(),
                    'values' => $distribution->values()->map(fn (mixed $total): int => (int) $total)->all(),
                ];
            }
        );
    }

    /**
     * @return Builder<KpiAssessment>
     */
    public function pendingReviewQuery(User $user, int $limit = 5): Builder
    {
        return $this->assessmentQuery($user)
            ->where('status', KpiAssessmentStatus::SUBMITTED->value)
            ->orderByDesc('submitted_at')
            ->limit($limit);
    }

    /**
     * @return Collection<int, array{title: string, description: string, occurred_at: string}>
     */
    public function getRecentActivity(User $user, int $limit = 6): Collection
    {
        return Cache::remember(
            $this->makeCacheKey($user, "activity:{$limit}"),
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            function () use ($user, $limit): Collection {
                if ($user->hasRole(SystemRole::SUPER_ADMIN->value) || $user->hasRole(SystemRole::HRD->value)) {
                    $activities = Activity::query()
                        ->with('causer')
                        ->where('log_name', 'pulsekpi')
                        ->whereIn('subject_type', [
                            KpiAssessment::class,
                            KpiTemplate::class,
                            KpiReportExport::class,
                        ])
                        ->latest()
                        ->limit($limit)
                        ->get();

                    if ($activities->isNotEmpty()) {
                        return $activities->map(
                            fn (Activity $activity): array => [
                                'title' => $this->formatActivityTitle($activity),
                                'description' => $this->formatActivityDescription($activity),
                                'occurred_at' => $activity->created_at?->diffForHumans() ?? '-',
                            ]
                        );
                    }
                }

                return $this->getRecentFallbackActivity($user, $limit);
            }
        );
    }

    /**
     * @return Collection<int, array{name: string, average_score: string, assessment_count: int}>
     */
    public function getDivisionPerformance(User $user, int $limit = 5): Collection
    {
        return Cache::remember(
            $this->makeCacheKey($user, "divisions:{$limit}"),
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            function () use ($user, $limit): Collection {
                return KpiAssessment::query()
                    ->visibleToUser($user)
                    ->join('users', 'users.id', '=', 'kpi_assessments.employee_id')
                    ->join('divisions', 'divisions.id', '=', 'users.division_id')
                    ->whereNotNull('kpi_assessments.final_score')
                    ->selectRaw('divisions.name as division_name, AVG(kpi_assessments.final_score) as average_score, COUNT(kpi_assessments.id) as assessment_count')
                    ->groupBy('divisions.id', 'divisions.name')
                    ->orderByDesc('average_score')
                    ->limit($limit)
                    ->get()
                    ->map(fn (object $row): array => [
                        'name' => (string) $row->division_name,
                        'average_score' => number_format((float) $row->average_score, 2),
                        'assessment_count' => (int) $row->assessment_count,
                    ]);
            }
        );
    }

    /**
     * @return Builder<KpiReportExport>
     */
    public function latestExportsQuery(User $user, int $limit = 5): Builder
    {
        $query = KpiReportExport::query()
            ->with(['requester', 'assessment.employee'])
            ->latest()
            ->limit($limit);

        if (
            $user->hasRole(SystemRole::SUPER_ADMIN->value)
            || $user->hasRole(SystemRole::HRD->value)
        ) {
            return $query;
        }

        return $query->where('requested_by', $user->getKey());
    }

    public function makeCacheKey(User $user, string $segment): string
    {
        return sprintf(
            'hrd-dashboard:%s:%s:%s',
            $user->getKey(),
            md5(json_encode($user->getRoleNames()->sort()->values()->all(), JSON_THROW_ON_ERROR)),
            $segment,
        );
    }

    /**
     * @return Builder<KpiAssessment>
     */
    protected function assessmentQuery(User $user): Builder
    {
        return $this->buildAssessmentQuery->execute($user);
    }

    /**
     * @return Builder<KpiAssignment>
     */
    protected function assignmentQuery(User $user): Builder
    {
        return $this->buildAssignmentQuery->execute($user);
    }

    /**
     * @return Builder<User>
     */
    protected function employeeScopeQuery(User $user): Builder
    {
        $query = User::query()->role(SystemRole::EMPLOYEE->value);

        if ($user->hasRole(SystemRole::SUPER_ADMIN->value) || $user->hasRole(SystemRole::HRD->value)) {
            return $query;
        }

        if ($user->isManager()) {
            return $query->where('supervisor_id', $user->getKey());
        }

        return $query->whereKey($user->getKey());
    }

    /**
     * @param  Builder<KpiAssessment>  $query
     */
    protected function countAssessmentsByStatus(Builder $query, KpiAssessmentStatus $status): int
    {
        return (clone $query)->where('status', $status->value)->count();
    }

    protected function formatActivityTitle(Activity $activity): string
    {
        $subject = class_basename((string) $activity->subject_type);

        return match ($subject) {
            'KpiAssessment' => 'Aktivitas assessment diperbarui',
            'KpiTemplate' => 'Template KPI diperbarui',
            'KpiReportExport' => 'Riwayat export berubah',
            default => 'Aktivitas PulseKPI terbaru',
        };
    }

    protected function formatActivityDescription(Activity $activity): string
    {
        $actor = $activity->causer?->name ?? 'Sistem';
        $description = trim((string) ($activity->description ?? $activity->event ?? ''));

        if ($description === '') {
            return "{$actor} mencatat aktivitas baru.";
        }

        return "{$actor}: {$description}";
    }

    /**
     * @return Collection<int, array{title: string, description: string, occurred_at: string}>
     */
    protected function getRecentFallbackActivity(User $user, int $limit): Collection
    {
        $assessmentItems = $this->assessmentQuery($user)
            ->whereIn('status', [
                KpiAssessmentStatus::SUBMITTED->value,
                KpiAssessmentStatus::REVIEWED->value,
                KpiAssessmentStatus::APPROVED->value,
                KpiAssessmentStatus::LOCKED->value,
            ])
            ->latest('updated_at')
            ->limit($limit)
            ->get()
            ->map(fn (KpiAssessment $assessment): array => [
                'title' => sprintf(
                    'Assessment %s',
                    $assessment->status instanceof KpiAssessmentStatus
                        ? $assessment->status->label()
                        : KpiAssessmentStatus::from((string) $assessment->status)->label()
                ),
                'description' => sprintf(
                    '%s pada periode %s',
                    $assessment->employee?->name ?? 'Karyawan',
                    $assessment->assignment?->period?->name ?? 'tanpa periode'
                ),
                'occurred_at' => $assessment->updated_at?->diffForHumans() ?? '-',
                'timestamp' => $assessment->updated_at?->timestamp ?? 0,
            ]);

        $exportItems = $this->latestExportsQuery($user, $limit)
            ->get()
            ->map(fn (KpiReportExport $export): array => [
                'title' => sprintf('Export %s', $export->status->label()),
                'description' => $export->file_name ?: sprintf('Permintaan %s', $export->type->label()),
                'occurred_at' => $export->created_at?->diffForHumans() ?? '-',
                'timestamp' => $export->created_at?->timestamp ?? 0,
            ]);

        $templateItems = KpiTemplate::query()
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->limit($limit)
            ->get()
            ->map(fn (KpiTemplate $template): array => [
                'title' => 'Template KPI dipublikasikan',
                'description' => $template->name,
                'occurred_at' => $template->published_at?->diffForHumans() ?? '-',
                'timestamp' => $template->published_at?->timestamp ?? 0,
            ]);

        return $assessmentItems
            ->concat($exportItems)
            ->concat($templateItems)
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values()
            ->map(fn (array $item): array => [
                'title' => $item['title'],
                'description' => $item['description'],
                'occurred_at' => $item['occurred_at'],
            ]);
    }
}
