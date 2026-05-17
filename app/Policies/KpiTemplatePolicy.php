<?php

namespace App\Policies;

use App\Enums\SystemPermission;
use App\Models\KpiTemplate;
use App\Models\User;

class KpiTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(SystemPermission::MANAGE_KPI_TEMPLATES->value)
            || $user->isManager();
    }

    public function view(User $user, KpiTemplate $template): bool
    {
        if ($user->can(SystemPermission::MANAGE_KPI_TEMPLATES->value)) {
            return true;
        }

        return $user->isManager()
            && $template->is_active
            && $template->published_at !== null;
    }

    public function create(User $user): bool
    {
        return $user->can(SystemPermission::MANAGE_KPI_TEMPLATES->value);
    }

    public function update(User $user, KpiTemplate $template): bool
    {
        return $user->can(SystemPermission::MANAGE_KPI_TEMPLATES->value)
            && $template->published_at === null;
    }

    public function delete(User $user, KpiTemplate $template): bool
    {
        return $user->can(SystemPermission::MANAGE_KPI_TEMPLATES->value)
            && $template->published_at === null;
    }

    public function restore(User $user, KpiTemplate $template): bool
    {
        return $this->update($user, $template);
    }

    public function forceDelete(User $user, KpiTemplate $template): bool
    {
        return $this->update($user, $template);
    }

    public function publish(User $user, KpiTemplate $template): bool
    {
        return $user->can(SystemPermission::MANAGE_KPI_TEMPLATES->value)
            && $template->published_at === null;
    }

    public function duplicate(User $user, KpiTemplate $template): bool
    {
        return $user->can(SystemPermission::MANAGE_KPI_TEMPLATES->value);
    }

    public function setActiveState(User $user, KpiTemplate $template): bool
    {
        return $user->can(SystemPermission::MANAGE_KPI_TEMPLATES->value);
    }
}
