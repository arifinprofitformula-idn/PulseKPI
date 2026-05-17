<?php

namespace App\Enums;

enum KpiPeriodType: string
{
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'Monthly',
            self::YEARLY => 'Yearly',
        };
    }

    public static function options(): array
    {
        return array_combine(
            array_map(fn (self $type): string => $type->value, self::cases()),
            array_map(fn (self $type): string => $type->label(), self::cases()),
        );
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $type): string => $type->value, self::cases());
    }
}
