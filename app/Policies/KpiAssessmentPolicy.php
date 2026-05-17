<?php

namespace App\Policies;

use App\Enums\KpiAssessmentStatus;
use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Models\KpiAssessment;
use App\Models\KpiAssignment;
use App\Models\User;

class KpiAssessmentPolicy
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
        return $user->hasRole(SystemRole::HRD->value)
            || $user->can(SystemPermission::SUBMIT_KPI_ASSESSMENT->value)
            || $user->hasRole(SystemRole::EMPLOYEE->value);
    }

    public function view(User $user, KpiAssessment $assessment): bool
    {
        if ($user->hasRole(SystemRole::HRD->value)) {
            return true;
        }

        if ($user->isManager()) {
            return $assessment->employee !== null && $user->manages($assessment->employee);
        }

        return $assessment->employee !== null && $user->is($assessment->employee);
    }

    public function create(User $user): bool
    {
        return $user->isManager() || $user->hasRole(SystemRole::SUPER_ADMIN->value);
    }

    public function createForAssignment(User $user, KpiAssignment $assignment): bool
    {
        if (! $this->create($user)) {
            return false;
        }

        if ($assignment->employee === null) {
            return false;
        }

        if ($user->is($assignment->employee)) {
            return false;
        }

        if ($user->isManager()) {
            return $user->manages($assignment->employee);
        }

        return true;
    }

    public function update(User $user, KpiAssessment $assessment): bool
    {
        if (! $assessment->isEditable()) {
            return false;
        }

        if ($user->isManager()) {
            return $assessment->employee !== null
                && ! $user->is($assessment->employee)
                && $user->manages($assessment->employee);
        }

        return false;
    }

    public function submit(User $user, KpiAssessment $assessment): bool
    {
        if (! in_array($assessment->status, [
            KpiAssessmentStatus::DRAFT,
            KpiAssessmentStatus::REJECTED,
        ], true)) {
            return false;
        }

        return $this->update($user, $assessment);
    }

    public function downloadEvidence(User $user, KpiAssessment $assessment): bool
    {
        if ($user->hasRole(SystemRole::HRD->value)) {
            return true;
        }

        if ($assessment->assessor !== null && $user->is($assessment->assessor)) {
            return true;
        }

        return $assessment->employee !== null && $user->is($assessment->employee);
    }

    public function delete(User $user, KpiAssessment $assessment): bool
    {
        return false;
    }
}
