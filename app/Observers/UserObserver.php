<?php

namespace App\Observers;

use App\Actions\Users\ValidateUserOrganizationHierarchy;
use App\Models\User;

class UserObserver
{
    public function __construct(
        private readonly ValidateUserOrganizationHierarchy $validateUserOrganizationHierarchy,
    ) {}

    public function saving(User $user): void
    {
        $this->validateUserOrganizationHierarchy->execute($user);
    }
}
