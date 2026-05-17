<?php

namespace App\Enums;

enum KpiReportExportType: string
{
    case ASSESSMENT_REPORT = 'assessment_report';
    case ASSESSMENT_DETAIL = 'assessment_detail';

    public function label(): string
    {
        return match ($this) {
            self::ASSESSMENT_REPORT => 'Assessment Report',
            self::ASSESSMENT_DETAIL => 'Assessment Detail',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(
            array_map(fn (self $type): string => $type->value, self::cases()),
            array_map(fn (self $type): string => $type->label(), self::cases()),
        );
    }
}
