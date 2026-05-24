<?php

namespace App\Actions\KpiAssignments;

use App\Enums\KpiAssignmentStatus;
use App\Enums\KpiPeriodType;
use App\Enums\SystemRole;
use App\Models\KpiAssignment;
use App\Models\KpiPeriod;
use App\Models\KpiTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class ValidateKpiAssignmentAction
{
    /**
     * @var list<string>
     */
    private const INVALID_EMPLOYMENT_STATUSES = [
        'inactive',
        'terminated',
        'resigned',
        'former',
    ];

    /**
     * @return array{period: KpiPeriod, template: KpiTemplate, employee: User}
     */
    public function execute(
        KpiPeriod|int $period,
        KpiTemplate|int $template,
        User|int $employee,
        ?KpiAssignment $ignore = null,
    ): array {
        $resolvedPeriod = $period instanceof KpiPeriod ? $period : KpiPeriod::query()->findOrFail($period);
        $resolvedTemplate = $template instanceof KpiTemplate ? $template : KpiTemplate::query()->findOrFail($template);
        $resolvedEmployee = $employee instanceof User ? $employee : User::query()->findOrFail($employee);

        $this->ensureResolved($resolvedPeriod, $resolvedTemplate, $resolvedEmployee, $ignore);

        return [
            'period' => $resolvedPeriod,
            'template' => $resolvedTemplate,
            'employee' => $resolvedEmployee,
        ];
    }

    public function ensureResolved(
        KpiPeriod $period,
        KpiTemplate $template,
        User $employee,
        ?KpiAssignment $ignore = null,
    ): void {
        $errors = $this->baseErrors($period, $template);

        if (! $this->isAssignableEmployee($employee, $period)) {
            $errors['employee_id'] = 'The selected employee is not eligible for KPI assignment.';
        }

        $duplicateExists = KpiAssignment::query()
            ->where('kpi_period_id', $period->getKey())
            ->where('employee_id', $employee->getKey())
            ->when($ignore !== null, fn (Builder $query) => $query->whereKeyNot($ignore->getKey()))
            ->exists();

        if ($duplicateExists) {
            $errors['employee_id'] = 'The selected employee already has an assignment for this period.';
        }

        if ($ignore !== null && $ignore->status === KpiAssignmentStatus::CANCELLED) {
            $errors['status'] = 'Cancelled KPI assignments cannot be edited.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function ensurePeriodAndTemplate(KpiPeriod $period, KpiTemplate $template): void
    {
        $errors = $this->baseErrors($period, $template);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function employeeBaseQuery(): Builder
    {
        return User::query()
            ->whereHas('roles', fn (Builder $query) => $query->whereIn('name', [
                SystemRole::EMPLOYEE->value,
                SystemRole::SUPERVISOR->value,
            ]))
            ->where(function (Builder $query): void {
                $query->whereNull('employment_status')
                    ->orWhereNotIn('employment_status', self::INVALID_EMPLOYMENT_STATUSES);
            });
    }

    public function isAssignableEmployee(User $employee, KpiPeriod $period): bool
    {
        if (! $employee->hasRole(SystemRole::EMPLOYEE->value)
            && ! $employee->hasRole(SystemRole::SUPERVISOR->value)) {
            return false;
        }

        $employmentStatus = $employee->employment_status !== null
            ? mb_strtolower($employee->employment_status)
            : null;

        if ($employmentStatus !== null && in_array($employmentStatus, self::INVALID_EMPLOYMENT_STATUSES, true)) {
            return false;
        }

        if (($employee->joined_at !== null)
            && Carbon::parse($employee->joined_at)->gt(Carbon::parse($period->ends_at))) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    private function baseErrors(KpiPeriod $period, KpiTemplate $template): array
    {
        $errors = [];

        if (! $period->is_active) {
            $errors['kpi_period_id'] = 'The selected KPI period must be active.';
        }

        if ($period->type === KpiPeriodType::MONTHLY && $period->month === null) {
            $errors['kpi_period_id'] = 'The selected monthly KPI period is invalid.';
        }

        if (! $template->is_active) {
            $errors['kpi_template_id'] = 'The selected KPI template must be active.';
        }

        if ($template->published_at === null) {
            $errors['kpi_template_id'] = 'Only published KPI templates can be assigned.';
        }

        return $errors;
    }
}
