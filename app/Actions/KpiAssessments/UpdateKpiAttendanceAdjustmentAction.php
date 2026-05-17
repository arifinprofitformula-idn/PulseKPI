<?php

namespace App\Actions\KpiAssessments;

use App\Models\KpiAttendanceAdjustment;
use App\Models\User;
use App\Services\Audit\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateKpiAttendanceAdjustmentAction
{
    public function __construct(
        private readonly CalculateKpiAssessmentScoreAction $calculateScore,
        private readonly ActivityLogService $activityLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(KpiAttendanceAdjustment|int $attendanceAdjustment, array $data, ?User $actor = null): KpiAttendanceAdjustment
    {
        $attendance = $attendanceAdjustment instanceof KpiAttendanceAdjustment
            ? $attendanceAdjustment->loadMissing('assessment')
            : KpiAttendanceAdjustment::query()->with('assessment')->findOrFail($attendanceAdjustment);

        $authorizer = $actor ?? Auth::user();

        if ($authorizer !== null) {
            Gate::forUser($authorizer)->authorize('update', $attendance->assessment);
        }

        if (! $attendance->assessment->isEditable()) {
            throw ValidationException::withMessages([
                'assessment' => 'Submitted assessments cannot be edited.',
            ]);
        }

        $validated = Validator::make($data, [
            'working_days' => ['required', 'integer', 'between:1,31'],
            'sick_days' => ['required', 'integer', 'min:0'],
            'permission_days' => ['required', 'integer', 'min:0'],
            'absent_days' => ['required', 'integer', 'min:0'],
            'leave_days' => ['required', 'integer', 'min:0'],
        ])->validate();

        $nonWorkingDays = (int) $validated['sick_days']
            + (int) $validated['permission_days']
            + (int) $validated['absent_days']
            + (int) $validated['leave_days'];

        if ($nonWorkingDays > (int) $validated['working_days']) {
            throw ValidationException::withMessages([
                'working_days' => 'Total non-working days cannot exceed working days.',
            ]);
        }

        DB::transaction(function () use ($attendance, $validated, $authorizer): void {
            $attendance->fill($validated);
            $attendance->save();

            $this->calculateScore->execute($attendance->assessment);

            $attendance->refresh();

            $this->activityLogService->log(
                'kpi_assessment_attendance.updated',
                $attendance,
                [
                    'assessment_id' => $attendance->kpi_assessment_id,
                    'assignment_id' => $attendance->assessment->kpi_assignment_id,
                    'employee_id' => $attendance->assessment->employee_id,
                    'working_days' => $attendance->working_days,
                    'sick_days' => $attendance->sick_days,
                    'permission_days' => $attendance->permission_days,
                    'absent_days' => $attendance->absent_days,
                    'leave_days' => $attendance->leave_days,
                    'deduction_score' => $attendance->deduction_score,
                    'attendance_score' => $attendance->attendance_score,
                ],
                $authorizer
            );
        });

        return $attendance->fresh(['assessment']);
    }
}
