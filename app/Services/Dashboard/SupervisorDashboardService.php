<?php

namespace App\Services\Dashboard;

use App\Enums\SystemRole;
use App\Models\KpiAssessment;
use App\Models\KpiAssignment;
use App\Models\User;
use App\Support\KpiStatusBadge;
use Illuminate\Database\Eloquent\Builder;

class SupervisorDashboardService extends ManagerDashboardService
{
    /**
     * @return array{
     *     metrics: array<int, array{label: string, value: string, description: string}>,
     *     pendingQueue: array<int, array{employee: string, period: string, template: string, status: string}>,
     *     recentAssessments: array<int, array{employee: string, period: string, status: string, score: string, updated_at: string}>,
     * }
     */
    public function getDashboardData(User $user): array
    {
        $metrics = $this->getSummaryMetrics($user);

        return [
            'metrics' => [
                [
                    'label' => 'Staff Bawahan',
                    'value' => (string) $metrics['team_members'],
                    'description' => 'Jumlah staff langsung dalam lingkup Anda.',
                ],
                [
                    'label' => 'Assignment Aktif',
                    'value' => (string) $metrics['assignments_to_assess'],
                    'description' => 'KPI aktif yang belum memiliki assessment.',
                ],
                [
                    'label' => 'Draft Assessment',
                    'value' => (string) $metrics['draft_assessments'],
                    'description' => 'Assessment yang masih dapat Anda lanjutkan.',
                ],
                [
                    'label' => 'Perlu Submit',
                    'value' => (string) $metrics['submitted_assessments'],
                    'description' => 'Assessment yang sudah disubmit dan masih terlihat sebagai status.',
                ],
                [
                    'label' => 'Rejected / Perlu Revisi',
                    'value' => (string) $metrics['rejected_assessments'],
                    'description' => 'Assessment yang perlu revisi sebelum dikirim ulang.',
                ],
                [
                    'label' => 'Rata-rata Score Staff',
                    'value' => number_format((float) $metrics['average_team_score'], 2),
                    'description' => 'Rata-rata final score staff yang sudah dinilai.',
                ],
            ],
            'pendingQueue' => $this->assessmentQueueQuery($user, 5)
                ->get()
                ->map(fn (KpiAssessment $assessment): array => $this->mapPendingQueueItem($assessment))
                ->all(),
            'recentAssessments' => $this->teamOverviewQuery($user, 5)
                ->get()
                ->filter(fn (KpiAssignment $assignment): bool => $assignment->assessment !== null)
                ->map(fn (KpiAssignment $assignment): array => $this->mapRecentAssessmentItem($assignment))
                ->values()
                ->all(),
        ];
    }

    public function makeCacheKey(User $user, string $segment): string
    {
        return sprintf('supervisor-dashboard:%s:%s', $user->getKey(), $segment);
    }

    /**
     * @return Builder<User>
     */
    protected function directReportQuery(User $user): Builder
    {
        return User::query()
            ->role(SystemRole::EMPLOYEE->value)
            ->where('supervisor_id', $user->getKey());
    }

    protected function applyDirectReportScope(Builder $builder, User $user): void
    {
        $builder
            ->where('supervisor_id', $user->getKey())
            ->whereHas('roles', fn (Builder $roleBuilder): Builder => $roleBuilder->where('name', SystemRole::EMPLOYEE->value));
    }

    /**
     * @return array{employee: string, period: string, template: string, status: string}
     */
    protected function mapPendingQueueItem(KpiAssessment $assessment): array
    {
        $employee = $assessment->employee;
        $assignment = $assessment->assignment;
        $period = $assignment?->period;
        $template = $assignment?->template;

        return [
            'employee' => $employee instanceof User ? $employee->name : '-',
            'period' => $period->name,
            'template' => $template->name,
            'status' => KpiStatusBadge::assessmentLabel($assessment->status),
        ];
    }

    /**
     * @return array{employee: string, period: string, status: string, score: string, updated_at: string}
     */
    protected function mapRecentAssessmentItem(KpiAssignment $assignment): array
    {
        $assessment = $assignment->assessment;

        return [
            'employee' => $assignment->employee instanceof User ? $assignment->employee->name : '-',
            'period' => $assignment->period->name,
            'status' => KpiStatusBadge::assessmentLabel($assessment->status),
            'score' => filled($assessment->final_score)
                ? number_format((float) $assessment->final_score, 2)
                : '-',
            'updated_at' => $assessment->updated_at?->diffForHumans() ?? '-',
        ];
    }
}
