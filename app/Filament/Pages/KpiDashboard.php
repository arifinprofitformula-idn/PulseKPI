<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class KpiDashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'KPI Dashboard';

    protected static ?string $title = 'KPI Dashboard';

    protected static ?int $navigationSort = 1;
}
