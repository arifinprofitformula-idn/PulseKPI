<?php

namespace App\Policies;

use App\Enums\SystemPermission;
use App\Models\KpiTemplate;
use App\Models\KpiTemplateItem;
use App\Models\User;

class KpiTemplateItemPolicy
{
    public function viewAny(User $user): bool
    {
        return app(KpiTemplatePolicy::class)->viewAny($user);
    }

    public function view(User $user, KpiTemplateItem $item): bool
    {
        return app(KpiTemplatePolicy::class)->view($user, $item->template);
    }

    public function create(User $user): bool
    {
        return $user->can(SystemPermission::MANAGE_KPI_TEMPLATES->value);
    }

    public function createForTemplate(User $user, KpiTemplate $template): bool
    {
        return $user->can(SystemPermission::MANAGE_KPI_TEMPLATES->value)
            && $template->published_at === null;
    }

    public function update(User $user, KpiTemplateItem $item): bool
    {
        return app(KpiTemplatePolicy::class)->update($user, $item->template);
    }

    public function delete(User $user, KpiTemplateItem $item): bool
    {
        return app(KpiTemplatePolicy::class)->delete($user, $item->template);
    }
}
