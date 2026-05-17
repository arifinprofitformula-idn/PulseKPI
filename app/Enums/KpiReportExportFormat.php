<?php

namespace App\Enums;

enum KpiReportExportFormat: string
{
    case XLSX = 'xlsx';
    case PDF = 'pdf';

    public function label(): string
    {
        return match ($this) {
            self::XLSX => 'Excel',
            self::PDF => 'PDF',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(
            array_map(fn (self $format): string => $format->value, self::cases()),
            array_map(fn (self $format): string => $format->label(), self::cases()),
        );
    }
}
