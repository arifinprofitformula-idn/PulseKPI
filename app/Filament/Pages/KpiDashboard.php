<?php

namespace App\Filament\Pages;

use App\Enums\SystemRole;
use App\Models\User;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class KpiDashboard extends BaseDashboard
{
    protected static \UnitEnum|string|null $navigationGroup = 'Dashboard';

    protected static ?string $navigationLabel = 'KPI Dashboard';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static ?string $title = 'KPI Dashboard';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $target = match (true) {
            $user->hasRole(SystemRole::MANAGER->value) => ManagerDashboard::getUrl(),
            $user->hasRole(SystemRole::APPROVER->value) => ApproverDashboard::getUrl(),
            default => null,
        };

        if ($target !== null && request()->path() === 'admin') {
            $this->redirect($target, navigate: true);
        }
    }
}
