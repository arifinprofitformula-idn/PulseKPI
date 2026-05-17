<?php

namespace App\Enums;

enum KpiAssessmentStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case REVIEWED = 'reviewed';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case LOCKED = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::REVIEWED => 'Reviewed',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::LOCKED => 'Locked',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(
            array_map(fn (self $status): string => $status->value, self::cases()),
            array_map(fn (self $status): string => $status->label(), self::cases()),
        );
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status): string => $status->value, self::cases());
    }
}
