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
    public function viewAny(User $user): bool
    {
        return $user->hasRole(SystemRole::SUPER_ADMIN->value)
            || $user->hasRole(SystemRole::HRD->value)
            || $user->hasRole(SystemRole::APPROVER->value)
            || $user->can(SystemPermission::SUBMIT_KPI_ASSESSMENT->value)
            || $user->hasRole(SystemRole::EMPLOYEE->value);
    }

    public function viewReports(User $user): bool
    {
        return $user->hasRole(SystemRole::SUPER_ADMIN->value)
            || $user->can(SystemPermission::VIEW_REPORTS->value);
    }

    public function viewDashboard(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function view(User $user, KpiAssessment $assessment): bool
    {
        if ($user->hasRole(SystemRole::SUPER_ADMIN->value) || $user->hasRole(SystemRole::HRD->value)) {
            return true;
        }

        if ($user->hasRole(SystemRole::APPROVER->value)) {
            return in_array($assessment->status, [
                KpiAssessmentStatus::REVIEWED,
                KpiAssessmentStatus::APPROVED,
                KpiAssessmentStatus::LOCKED,
            ], true);
        }

        if ($user->isManager()) {
            return $assessment->employee !== null && $user->manages($assessment->employee);
        }

        return $assessment->employee !== null && $user->is($assessment->employee);
    }

    public function exportPdf(User $user, KpiAssessment $assessment): bool
    {
        if ($assessment->status === KpiAssessmentStatus::DRAFT) {
            return false;
        }

        return $this->view($user, $assessment);
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

        if ($user->hasRole(SystemRole::SUPER_ADMIN->value)) {
            return true;
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
        if (! $assessment->isEditable()) {
            return false;
        }

        return $this->update($user, $assessment);
    }

    public function review(User $user, KpiAssessment $assessment): bool
    {
        if ($assessment->status !== KpiAssessmentStatus::SUBMITTED) {
            return false;
        }

        return $user->hasRole(SystemRole::SUPER_ADMIN->value)
            || $user->hasRole(SystemRole::HRD->value);
    }

    public function approve(User $user, KpiAssessment $assessment): bool
    {
        if ($assessment->status !== KpiAssessmentStatus::REVIEWED) {
            return false;
        }

        return $user->hasRole(SystemRole::SUPER_ADMIN->value)
            || $user->hasRole(SystemRole::APPROVER->value);
    }

    public function reject(User $user, KpiAssessment $assessment): bool
    {
        if ($user->hasRole(SystemRole::SUPER_ADMIN->value)) {
            return in_array($assessment->status, [
                KpiAssessmentStatus::SUBMITTED,
                KpiAssessmentStatus::REVIEWED,
            ], true);
        }

        if ($user->hasRole(SystemRole::HRD->value)) {
            return $assessment->status === KpiAssessmentStatus::SUBMITTED;
        }

        if ($user->hasRole(SystemRole::APPROVER->value)) {
            return $assessment->status === KpiAssessmentStatus::REVIEWED;
        }

        return false;
    }

    public function lock(User $user, KpiAssessment $assessment): bool
    {
        if ($assessment->status !== KpiAssessmentStatus::APPROVED) {
            return false;
        }

        return $user->hasRole(SystemRole::SUPER_ADMIN->value)
            || $user->hasRole(SystemRole::APPROVER->value);
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
