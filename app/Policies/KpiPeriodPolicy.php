<?php

namespace App\Policies;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Models\KpiPeriod;
use App\Models\User;

class KpiPeriodPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(SystemRole::SUPER_ADMIN->value)) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can(SystemPermission::ASSIGN_KPI->value);
    }

    public function view(User $user, KpiPeriod $period): bool
    {
        return $user->can(SystemPermission::ASSIGN_KPI->value);
    }

    public function create(User $user): bool
    {
        return $user->can(SystemPermission::ASSIGN_KPI->value);
    }

    public function update(User $user, KpiPeriod $period): bool
    {
        return $user->can(SystemPermission::ASSIGN_KPI->value);
    }

    public function delete(User $user, KpiPeriod $period): bool
    {
        if ($period->assignments()->exists()) {
            return false;
        }

        return $user->can(SystemPermission::ASSIGN_KPI->value);
    }
}
