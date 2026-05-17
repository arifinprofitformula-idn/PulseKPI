<?php

namespace App\Policies;

use App\Enums\SystemPermission;
use App\Models\KpiScoreRule;
use App\Models\KpiTemplateItem;
use App\Models\User;

class KpiScoreRulePolicy
{
    public function viewAny(User $user): bool
    {
        return app(KpiTemplatePolicy::class)->viewAny($user);
    }

    public function view(User $user, KpiScoreRule $scoreRule): bool
    {
        return app(KpiTemplatePolicy::class)->view($user, $scoreRule->item->template);
    }

    public function create(User $user): bool
    {
        return $user->can(SystemPermission::MANAGE_KPI_TEMPLATES->value);
    }

    public function createForItem(User $user, KpiTemplateItem $item): bool
    {
        return $user->can(SystemPermission::MANAGE_KPI_TEMPLATES->value)
            && $item->template->published_at === null;
    }

    public function update(User $user, KpiScoreRule $scoreRule): bool
    {
        return app(KpiTemplatePolicy::class)->update($user, $scoreRule->item->template);
    }

    public function delete(User $user, KpiScoreRule $scoreRule): bool
    {
        return app(KpiTemplatePolicy::class)->delete($user, $scoreRule->item->template);
    }
}
