<?php

namespace App\Actions\KpiAssessments;

use App\Models\KpiAssessment;
use App\Models\KpiAssessmentItem;
use App\Models\KpiAttendanceAdjustment;
use Illuminate\Support\Collection;

class CalculateKpiAssessmentScoreAction
{
    public function execute(KpiAssessment $assessment): KpiAssessment
    {
        if (! $assessment->isEditable()) {
            return $assessment;
        }

        $assessment->load([
            'items.templateItem',
            'attendanceAdjustment',
        ]);

        /** @var Collection<int, KpiAssessmentItem> $items */
        $items = $assessment->items;
        $attendance = $assessment->attendanceAdjustment ?? new KpiAttendanceAdjustment;

        $kpiScoreHundredths = 0;

        foreach ($items as $item) {
            $weightedScoreHundredths = $this->calculateWeightedScoreHundredths(
                $item->score,
                (string) $item->template_item_weight
            );

            if ((string) $item->weighted_score !== $this->fromHundredths($weightedScoreHundredths)) {
                $item->forceFill([
                    'weighted_score' => $this->fromHundredths($weightedScoreHundredths),
                ])->saveQuietly();
            }

            $kpiScoreHundredths += $weightedScoreHundredths;
        }

        $attendanceDeductionHundredths = $this->calculateAttendanceDeductionHundredths($attendance);
        $attendanceScoreHundredths = max(0, 10000 - $attendanceDeductionHundredths);
        $finalScoreHundredths = max(0, $kpiScoreHundredths - $attendanceDeductionHundredths);

        if ($attendance->exists) {
            $attendance->forceFill([
                'deduction_score' => $this->fromHundredths($attendanceDeductionHundredths),
                'attendance_score' => $this->fromHundredths($attendanceScoreHundredths),
            ])->saveQuietly();
        }

        $assessment->forceFill([
            'kpi_score' => $this->fromHundredths($kpiScoreHundredths),
            'attendance_score' => $this->fromHundredths($attendanceScoreHundredths),
            'attendance_deduction' => $this->fromHundredths($attendanceDeductionHundredths),
            'final_score' => $this->fromHundredths($finalScoreHundredths),
            'grade' => $this->resolveGrade($finalScoreHundredths),
        ])->saveQuietly();

        return $assessment->fresh([
            'assignment.period',
            'assignment.template',
            'employee',
            'assessor',
            'items.templateItem',
            'attendanceAdjustment',
        ]);
    }

    public function calculateWeightedScoreHundredths(?int $score, string $weight): int
    {
        $weightHundredths = $this->toHundredths($weight);

        return match ($score) {
            2 => $weightHundredths,
            1 => intdiv($weightHundredths, 2),
            default => 0,
        };
    }

    public function calculateAttendanceDeductionHundredths(KpiAttendanceAdjustment $attendance): int
    {
        return ($attendance->sick_days * 10)
            + ($attendance->permission_days * 20)
            + ($attendance->absent_days * 70);
    }

    public function resolveGrade(int $finalScoreHundredths): string
    {
        return match (true) {
            $finalScoreHundredths >= 9000 => 'Excellent',
            $finalScoreHundredths >= 8000 => 'Good',
            $finalScoreHundredths >= 7000 => 'Fair',
            default => 'Needs Improvement',
        };
    }

    public function toHundredths(string $value): int
    {
        $normalized = trim($value);

        if ($normalized === '') {
            return 0;
        }

        $negative = str_starts_with($normalized, '-');
        $normalized = ltrim($normalized, '+-');

        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '0');
        $fraction = substr(str_pad($fraction, 2, '0'), 0, 2);

        $amount = ((int) $whole * 100) + (int) $fraction;

        return $negative ? -$amount : $amount;
    }

    public function fromHundredths(int $value): string
    {
        $negative = $value < 0 ? '-' : '';
        $value = abs($value);

        return sprintf('%s%d.%02d', $negative, intdiv($value, 100), $value % 100);
    }
}
