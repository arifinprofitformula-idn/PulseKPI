<?php

namespace App\Enums;

enum KpiApprovalAction: string
{
    case REVIEWED = 'reviewed';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case LOCKED = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::REVIEWED => 'Reviewed',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::LOCKED => 'Locked',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $action): string => $action->value, self::cases());
    }
}
