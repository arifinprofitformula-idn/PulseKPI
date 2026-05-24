<?php

namespace App\Policies;

use App\Enums\KpiAssignmentStatus;
use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Models\KpiAssignment;
use App\Models\User;

class KpiAssignmentPolicy
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
        return $user->can(SystemPermission::ASSIGN_KPI->value)
            || $user->canAssessDirectReports()
            || $user->hasRole(SystemRole::EMPLOYEE->value);
    }

    public function view(User $user, KpiAssignment $assignment): bool
    {
        if ($user->can(SystemPermission::ASSIGN_KPI->value)) {
            return true;
        }

        if ($user->canAssessDirectReports()) {
            return $assignment->employee !== null
                && (! $user->isSupervisor() || $assignment->employee->hasRole(SystemRole::EMPLOYEE->value))
                && $user->manages($assignment->employee);
        }

        return $user->is($assignment->employee);
    }

    public function create(User $user): bool
    {
        return $user->can(SystemPermission::ASSIGN_KPI->value);
    }

    public function update(User $user, KpiAssignment $assignment): bool
    {
        return $user->can(SystemPermission::ASSIGN_KPI->value)
            && $assignment->status !== KpiAssignmentStatus::CANCELLED;
    }

    public function delete(User $user, KpiAssignment $assignment): bool
    {
        return false;
    }

    public function restore(User $user, KpiAssignment $assignment): bool
    {
        return false;
    }

    public function forceDelete(User $user, KpiAssignment $assignment): bool
    {
        return false;
    }

    public function cancel(User $user, KpiAssignment $assignment): bool
    {
        return $user->can(SystemPermission::ASSIGN_KPI->value)
            && $assignment->status !== KpiAssignmentStatus::CANCELLED;
    }

    public function bulkAssign(User $user): bool
    {
        return $user->can(SystemPermission::ASSIGN_KPI->value);
    }
}
