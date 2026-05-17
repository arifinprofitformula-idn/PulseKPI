<?php

namespace App\Policies;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Models\User;

class UserPolicy
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
        return $user->can(SystemPermission::MANAGE_USERS->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        if ($user->can(SystemPermission::MANAGE_USERS->value)) {
            return true;
        }

        if ($user->is($model)) {
            return true;
        }

        return $user->isManager() && $user->manages($model);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can(SystemPermission::MANAGE_USERS->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        return $user->can(SystemPermission::MANAGE_USERS->value);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->can(SystemPermission::MANAGE_USERS->value);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->can(SystemPermission::MANAGE_USERS->value);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->can(SystemPermission::MANAGE_USERS->value);
    }
}
