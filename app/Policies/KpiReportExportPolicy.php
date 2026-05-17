<?php

namespace App\Policies;

use App\Enums\KpiReportExportStatus;
use App\Enums\SystemRole;
use App\Models\KpiReportExport;
use App\Models\User;

class KpiReportExportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(SystemRole::SUPER_ADMIN->value)
            || $user->hasRole(SystemRole::HRD->value)
            || $user->hasRole(SystemRole::MANAGER->value)
            || $user->hasRole(SystemRole::APPROVER->value);
    }

    public function view(User $user, KpiReportExport $export): bool
    {
        if ($user->hasRole(SystemRole::SUPER_ADMIN->value) || $user->hasRole(SystemRole::HRD->value)) {
            return true;
        }

        return $export->requested_by === $user->getKey();
    }

    public function download(User $user, KpiReportExport $export): bool
    {
        if (! $this->view($user, $export)) {
            return false;
        }

        return $export->status === KpiReportExportStatus::COMPLETED
            && filled($export->file_path)
            && filled($export->file_name);
    }
}
