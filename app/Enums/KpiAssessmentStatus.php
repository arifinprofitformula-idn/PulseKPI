<?php

namespace App\Enums;

enum KpiAssessmentStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::REJECTED => 'Rejected',
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
