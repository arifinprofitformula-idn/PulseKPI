<?php

namespace App\Enums;

enum KpiAssignmentStatus: string
{
    case DRAFT = 'draft';
    case ASSIGNED = 'assigned';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::ASSIGNED => 'Assigned',
            self::CANCELLED => 'Cancelled',
        };
    }

    public static function options(): array
    {
        return array_combine(
            array_map(fn (self $status): string => $status->value, self::cases()),
            array_map(fn (self $status): string => $status->label(), self::cases()),
        );
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $status): string => $status->value, self::cases());
    }
}
