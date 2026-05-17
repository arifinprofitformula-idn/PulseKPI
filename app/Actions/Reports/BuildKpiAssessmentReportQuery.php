<?php

namespace App\Actions\Reports;

use App\Models\KpiAssessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BuildKpiAssessmentReportQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<KpiAssessment>
     */
    public function execute(User $user, array $filters = []): Builder
    {
        $query = KpiAssessment::query()
            ->with([
                'assignment.period',
                'assignment.template',
                'employee.division',
                'employee.department',
                'employee.position',
                'assessor',
            ])
            ->visibleToUser($user);

        return $this->applyFilters($query, $filters);
    }

    /**
     * @param  Builder<KpiAssessment>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<KpiAssessment>
     */
    public function applyFilters(Builder $query, array $filters = []): Builder
    {
        $periodId = $this->normalizeNullableInteger($filters['period_id'] ?? null);
        if ($periodId !== null) {
            $query->whereHas('assignment', fn (Builder $builder) => $builder->where('kpi_period_id', $periodId));
        }

        $year = $this->normalizeNullableInteger($filters['year'] ?? null);
        if ($year !== null) {
            $query->whereHas('assignment.period', fn (Builder $builder) => $builder->where('year', $year));
        }

        $month = $this->normalizeNullableInteger($filters['month'] ?? null);
        if ($month !== null) {
            $query->whereHas('assignment.period', fn (Builder $builder) => $builder->where('month', $month));
        }

        $divisionId = $this->normalizeNullableInteger($filters['division_id'] ?? null);
        if ($divisionId !== null) {
            $query->whereHas('employee', fn (Builder $builder) => $builder->where('division_id', $divisionId));
        }

        $departmentId = $this->normalizeNullableInteger($filters['department_id'] ?? null);
        if ($departmentId !== null) {
            $query->whereHas('employee', fn (Builder $builder) => $builder->where('department_id', $departmentId));
        }

        $positionId = $this->normalizeNullableInteger($filters['position_id'] ?? null);
        if ($positionId !== null) {
            $query->whereHas('employee', fn (Builder $builder) => $builder->where('position_id', $positionId));
        }

        $assessorId = $this->normalizeNullableInteger($filters['assessor_id'] ?? null);
        if ($assessorId !== null) {
            $query->where('assessor_id', $assessorId);
        }

        $status = $this->normalizeNullableString($filters['status'] ?? null);
        if ($status !== null) {
            $query->where('status', $status);
        }

        $grade = $this->normalizeNullableString($filters['grade'] ?? null);
        if ($grade !== null) {
            $query->where('grade', $grade);
        }

        $minFinalScore = $this->normalizeNullableNumeric($filters['min_final_score'] ?? null);
        if ($minFinalScore !== null) {
            $query->where('final_score', '>=', $minFinalScore);
        }

        $maxFinalScore = $this->normalizeNullableNumeric($filters['max_final_score'] ?? null);
        if ($maxFinalScore !== null) {
            $query->where('final_score', '<=', $maxFinalScore);
        }

        return $query;
    }

    protected function normalizeNullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    protected function normalizeNullableNumeric(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    protected function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
