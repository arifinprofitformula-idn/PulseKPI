<?php

namespace App\Policies;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Models\Division;
use App\Models\User;

class DivisionPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(SystemRole::SUPER_ADMIN->value)) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(SystemPermission::MANAGE_ORGANIZATION->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Division $division): bool
    {
        return $user->can(SystemPermission::MANAGE_ORGANIZATION->value);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can(SystemPermission::MANAGE_ORGANIZATION->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Division $division): bool
    {
        return $user->can(SystemPermission::MANAGE_ORGANIZATION->value);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Division $division): bool
    {
        return $user->can(SystemPermission::MANAGE_ORGANIZATION->value);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Division $division): bool
    {
        return $user->can(SystemPermission::MANAGE_ORGANIZATION->value);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Division $division): bool
    {
        return $user->can(SystemPermission::MANAGE_ORGANIZATION->value);
    }
}
