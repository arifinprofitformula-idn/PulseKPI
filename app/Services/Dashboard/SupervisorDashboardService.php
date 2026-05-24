<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Support\KpiStatusBadge;

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
            'pendingQueue' => $this->assessmentQueueQuery($user, 6)
                ->get()
                ->map(fn ($assessment): array => [
                    'employee' => $assessment->employee?->name ?? '-',
                    'period' => $assessment->assignment?->period?->name ?? '-',
                    'template' => $assessment->assignment?->template?->name ?? '-',
                    'status' => KpiStatusBadge::assessmentLabel($assessment->status),
                ])
                ->all(),
            'recentAssessments' => $this->teamOverviewQuery($user, 6)
                ->get()
                ->filter(fn ($assignment): bool => $assignment->assessment !== null)
                ->map(fn ($assignment): array => [
                    'employee' => $assignment->employee?->name ?? '-',
                    'period' => $assignment->period?->name ?? '-',
                    'status' => KpiStatusBadge::assessmentLabel($assignment->assessment->status),
                    'score' => filled($assignment->assessment->final_score)
                        ? number_format((float) $assignment->assessment->final_score, 2)
                        : '-',
                    'updated_at' => $assignment->assessment->updated_at?->diffForHumans() ?? '-',
                ])
                ->values()
                ->all(),
        ];
    }

    public function makeCacheKey(User $user, string $segment): string
    {
        return sprintf('supervisor-dashboard:%s:%s', $user->getKey(), $segment);
    }
}
