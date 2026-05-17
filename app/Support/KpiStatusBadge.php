<?php

namespace App\Support;

use App\Enums\KpiAssessmentStatus;
use App\Enums\KpiAssignmentStatus;

class KpiStatusBadge
{
    public static function assessmentLabel(mixed $state): string
    {
        return static::normalizeAssessmentStatus($state)->label();
    }

    public static function assessmentColor(mixed $state): string
    {
        return match (static::normalizeAssessmentStatus($state)) {
            KpiAssessmentStatus::DRAFT => 'gray',
            KpiAssessmentStatus::SUBMITTED => 'warning',
            KpiAssessmentStatus::REVIEWED => 'indigo',
            KpiAssessmentStatus::APPROVED => 'success',
            KpiAssessmentStatus::REJECTED => 'danger',
            KpiAssessmentStatus::LOCKED => 'slate',
        };
    }

    public static function assignmentColor(mixed $state): string
    {
        return match (static::normalizeAssignmentStatus($state)) {
            KpiAssignmentStatus::DRAFT => 'gray',
            KpiAssignmentStatus::ASSIGNED => 'info',
            KpiAssignmentStatus::CANCELLED => 'danger',
        };
    }

    protected static function normalizeAssessmentStatus(mixed $state): KpiAssessmentStatus
    {
        if ($state instanceof KpiAssessmentStatus) {
            return $state;
        }

        return KpiAssessmentStatus::from((string) $state);
    }

    protected static function normalizeAssignmentStatus(mixed $state): KpiAssignmentStatus
    {
        if ($state instanceof KpiAssignmentStatus) {
            return $state;
        }

        return KpiAssignmentStatus::from((string) $state);
    }
}
