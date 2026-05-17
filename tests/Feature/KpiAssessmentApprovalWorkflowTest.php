<?php

use App\Actions\KpiAssessments\ApproveKpiAssessmentAction;
use App\Actions\KpiAssessments\CreateKpiAssessmentAction;
use App\Actions\KpiAssessments\LockKpiAssessmentAction;
use App\Actions\KpiAssessments\RejectKpiAssessmentAction;
use App\Actions\KpiAssessments\ReviewKpiAssessmentAction;
use App\Actions\KpiAssessments\SubmitKpiAssessmentAction;
use App\Actions\KpiAssessments\UpdateKpiAssessmentAction;
use App\Actions\KpiAssessments\UpdateKpiAssessmentItemAction;
use App\Actions\KpiAssessments\UpdateKpiAttendanceAdjustmentAction;
use App\Enums\KpiApprovalAction as KpiApprovalActionEnum;
use App\Enums\KpiAssessmentStatus;
use App\Enums\KpiAssignmentStatus;
use App\Enums\SystemRole;
use App\Events\KpiAssessmentApproved;
use App\Events\KpiAssessmentLocked;
use App\Events\KpiAssessmentRejected;
use App\Events\KpiAssessmentReviewed;
use App\Filament\Resources\KpiAssessments\KpiAssessmentResource;
use App\Models\KpiApproval;
use App\Models\KpiAssessment;
use App\Models\KpiAssignment;
use App\Models\KpiPeriod;
use App\Models\KpiTemplate;
use App\Models\KpiTemplateItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makeApprovalWorkflowUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function makeApprovalWorkflowTemplate(int $itemCount = 2): KpiTemplate
{
    $template = KpiTemplate::factory()->create([
        'is_active' => true,
    ]);

    $weights = match ($itemCount) {
        1 => ['100.00'],
        2 => ['60.00', '40.00'],
        default => array_fill(0, $itemCount, sprintf('%.2f', 100 / $itemCount)),
    };

    foreach (range(1, $itemCount) as $index) {
        KpiTemplateItem::factory()->create([
            'kpi_template_id' => $template->getKey(),
            'sort_order' => $index,
            'name' => "Workflow Item {$index}",
            'weight' => $weights[$index - 1],
            'target_description' => "Target {$index}",
            'data_source' => "Source {$index}",
            'is_required' => true,
        ]);
    }

    $template->updateQuietly([
        'published_at' => now(),
    ]);

    return $template->fresh('items');
}

function makeApprovalWorkflowAssignment(User $employee, array $attributes = []): KpiAssignment
{
    $period = KpiPeriod::factory()->yearly()->create(['is_active' => true]);
    $template = makeApprovalWorkflowTemplate();

    return KpiAssignment::factory()->create(array_merge([
        'kpi_period_id' => $period->getKey(),
        'kpi_template_id' => $template->getKey(),
        'employee_id' => $employee->getKey(),
        'status' => KpiAssignmentStatus::ASSIGNED->value,
        'assigned_at' => now(),
        'cancelled_at' => null,
    ], $attributes));
}

function createSubmittedWorkflowAssessment(User $manager, User $employee): KpiAssessment
{
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeApprovalWorkflowAssignment($employee);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);

    foreach ($assessment->items as $item) {
        app(UpdateKpiAssessmentItemAction::class)->execute($item, [
            'item_id' => $item->getKey(),
            'score' => 2,
        ], $manager);
    }

    return app(SubmitKpiAssessmentAction::class)->execute($assessment, $manager);
}

it('hrd can review submitted assessment', function () {
    Event::fake([KpiAssessmentReviewed::class]);

    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $hrd = makeApprovalWorkflowUser(SystemRole::HRD->value);
    $assessment = createSubmittedWorkflowAssessment($manager, $employee);

    $reviewed = app(ReviewKpiAssessmentAction::class)->execute($assessment, [], $hrd);

    expect($reviewed->status)->toBe(KpiAssessmentStatus::REVIEWED)
        ->and($reviewed->reviewed_at)->not->toBeNull();

    Event::assertDispatched(KpiAssessmentReviewed::class);
});

it('hrd cannot review draft assessment', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $hrd = makeApprovalWorkflowUser(SystemRole::HRD->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeApprovalWorkflowAssignment($employee);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);

    expect(fn () => app(ReviewKpiAssessmentAction::class)->execute($assessment, [], $hrd))
        ->toThrow(AuthorizationException::class);
});

it('employee cannot review', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $assessment = createSubmittedWorkflowAssessment($manager, $employee);

    expect(fn () => app(ReviewKpiAssessmentAction::class)->execute($assessment, [], $employee))
        ->toThrow(AuthorizationException::class);
});

it('manager cannot review', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $assessment = createSubmittedWorkflowAssessment($manager, $employee);

    expect(fn () => app(ReviewKpiAssessmentAction::class)->execute($assessment, [], $manager))
        ->toThrow(AuthorizationException::class);
});

it('review creates approval history and audit log', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $hrd = makeApprovalWorkflowUser(SystemRole::HRD->value);
    $assessment = createSubmittedWorkflowAssessment($manager, $employee);

    app(ReviewKpiAssessmentAction::class)->execute($assessment, ['notes' => 'Ready for approver.'], $hrd);

    $approval = KpiApproval::query()->where('kpi_assessment_id', $assessment->getKey())->latest('id')->first();

    expect($approval)->not->toBeNull()
        ->and($approval?->action)->toBe(KpiApprovalActionEnum::REVIEWED)
        ->and($approval?->actor_id)->toBe($hrd->getKey())
        ->and($approval?->from_status)->toBe(KpiAssessmentStatus::SUBMITTED->value)
        ->and($approval?->to_status)->toBe(KpiAssessmentStatus::REVIEWED->value);

    $this->assertDatabaseHas('activity_log', [
        'event' => 'kpi_assessment.reviewed',
    ]);
});

it('approver can approve reviewed assessment', function () {
    Event::fake([KpiAssessmentApproved::class]);

    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $hrd = makeApprovalWorkflowUser(SystemRole::HRD->value);
    $approver = makeApprovalWorkflowUser(SystemRole::APPROVER->value);
    $assessment = createSubmittedWorkflowAssessment($manager, $employee);
    $assessment = app(ReviewKpiAssessmentAction::class)->execute($assessment, [], $hrd);

    $approved = app(ApproveKpiAssessmentAction::class)->execute($assessment, [], $approver);

    expect($approved->status)->toBe(KpiAssessmentStatus::APPROVED)
        ->and($approved->approved_at)->not->toBeNull();

    Event::assertDispatched(KpiAssessmentApproved::class);
});

it('approver cannot approve submitted assessment directly', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $approver = makeApprovalWorkflowUser(SystemRole::APPROVER->value);
    $assessment = createSubmittedWorkflowAssessment($manager, $employee);

    expect(fn () => app(ApproveKpiAssessmentAction::class)->execute($assessment, [], $approver))
        ->toThrow(AuthorizationException::class);
});

it('hrd cannot approve unless explicitly allowed', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $hrd = makeApprovalWorkflowUser(SystemRole::HRD->value);
    $assessment = createSubmittedWorkflowAssessment($manager, $employee);
    $assessment = app(ReviewKpiAssessmentAction::class)->execute($assessment, [], $hrd);

    expect(fn () => app(ApproveKpiAssessmentAction::class)->execute($assessment, [], $hrd))
        ->toThrow(AuthorizationException::class);
});

it('approval creates approval history and audit log', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $hrd = makeApprovalWorkflowUser(SystemRole::HRD->value);
    $approver = makeApprovalWorkflowUser(SystemRole::APPROVER->value);
    $assessment = createSubmittedWorkflowAssessment($manager, $employee);
    $assessment = app(ReviewKpiAssessmentAction::class)->execute($assessment, [], $hrd);

    app(ApproveKpiAssessmentAction::class)->execute($assessment, ['notes' => 'Approved for finalization.'], $approver);

    $approval = KpiApproval::query()->where('kpi_assessment_id', $assessment->getKey())->latest('id')->first();

    expect($approval)->not->toBeNull()
        ->and($approval?->action)->toBe(KpiApprovalActionEnum::APPROVED)
        ->and($approval?->actor_id)->toBe($approver->getKey())
        ->and($approval?->from_status)->toBe(KpiAssessmentStatus::REVIEWED->value)
        ->and($approval?->to_status)->toBe(KpiAssessmentStatus::APPROVED->value);

    $this->assertDatabaseHas('activity_log', [
        'event' => 'kpi_assessment.approved',
    ]);
});

it('hrd can reject submitted assessment with notes', function () {
    Event::fake([KpiAssessmentRejected::class]);

    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $hrd = makeApprovalWorkflowUser(SystemRole::HRD->value);
    $assessment = createSubmittedWorkflowAssessment($manager, $employee);

    $rejected = app(RejectKpiAssessmentAction::class)->execute($assessment, ['notes' => 'Please correct the evidence summary.'], $hrd);

    expect($rejected->status)->toBe(KpiAssessmentStatus::REJECTED)
        ->and($rejected->rejected_at)->not->toBeNull();

    Event::assertDispatched(KpiAssessmentRejected::class);
});

it('approver can reject reviewed assessment with notes', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $hrd = makeApprovalWorkflowUser(SystemRole::HRD->value);
    $approver = makeApprovalWorkflowUser(SystemRole::APPROVER->value);
    $assessment = createSubmittedWorkflowAssessment($manager, $employee);
    $assessment = app(ReviewKpiAssessmentAction::class)->execute($assessment, [], $hrd);

    $rejected = app(RejectKpiAssessmentAction::class)->execute($assessment, ['notes' => 'Need revised KPI justification.'], $approver);

    expect($rejected->status)->toBe(KpiAssessmentStatus::REJECTED)
        ->and($rejected->rejected_at)->not->toBeNull();
});

it('rejection requires notes', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $hrd = makeApprovalWorkflowUser(SystemRole::HRD->value);
    $assessment = createSubmittedWorkflowAssessment($manager, $employee);

    expect(fn () => app(RejectKpiAssessmentAction::class)->execute($assessment, [], $hrd))
        ->toThrow(ValidationException::class);
});

it('rejected assessment can be edited and resubmitted by authorized manager', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $hrd = makeApprovalWorkflowUser(SystemRole::HRD->value);
    $assessment = createSubmittedWorkflowAssessment($manager, $employee);
    $assessment = app(RejectKpiAssessmentAction::class)->execute($assessment, ['notes' => 'Add more detail.'], $hrd);

    app(UpdateKpiAssessmentAction::class)->execute($assessment, [
        'notes' => 'Revised after HRD feedback.',
    ], $manager);

    $firstItem = $assessment->items()->firstOrFail();

    app(UpdateKpiAssessmentItemAction::class)->execute($firstItem, [
        'item_id' => $firstItem->getKey(),
        'score' => 1,
        'evidence_note' => 'Updated after rejection.',
    ], $manager);

    $resubmitted = app(SubmitKpiAssessmentAction::class)->execute($assessment->fresh('items.templateItem', 'attendanceAdjustment', 'assignment'), $manager);

    expect($resubmitted->status)->toBe(KpiAssessmentStatus::SUBMITTED)
        ->and($resubmitted->submitted_at)->not->toBeNull();
});

it('rejection creates approval history and audit log', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $hrd = makeApprovalWorkflowUser(SystemRole::HRD->value);
    $assessment = createSubmittedWorkflowAssessment($manager, $employee);

    app(RejectKpiAssessmentAction::class)->execute($assessment, ['notes' => 'Rejected by HRD.'], $hrd);

    $approval = KpiApproval::query()->where('kpi_assessment_id', $assessment->getKey())->latest('id')->first();

    expect($approval)->not->toBeNull()
        ->and($approval?->action)->toBe(KpiApprovalActionEnum::REJECTED)
        ->and($approval?->notes)->toBe('Rejected by HRD.');

    $this->assertDatabaseHas('activity_log', [
        'event' => 'kpi_assessment.rejected',
    ]);
});

it('approver can lock approved assessment', function () {
    Event::fake([KpiAssessmentLocked::class]);

    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $hrd = makeApprovalWorkflowUser(SystemRole::HRD->value);
    $approver = makeApprovalWorkflowUser(SystemRole::APPROVER->value);
    $assessment = createSubmittedWorkflowAssessment($manager, $employee);
    $assessment = app(ReviewKpiAssessmentAction::class)->execute($assessment, [], $hrd);
    $assessment = app(ApproveKpiAssessmentAction::class)->execute($assessment, [], $approver);

    $locked = app(LockKpiAssessmentAction::class)->execute($assessment, [], $approver);

    expect($locked->status)->toBe(KpiAssessmentStatus::LOCKED)
        ->and($locked->locked_at)->not->toBeNull();

    Event::assertDispatched(KpiAssessmentLocked::class);
});

it('cannot lock submitted reviewed or rejected assessment directly', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $hrd = makeApprovalWorkflowUser(SystemRole::HRD->value);
    $approver = makeApprovalWorkflowUser(SystemRole::APPROVER->value);
    $submitted = createSubmittedWorkflowAssessment($manager, $employee);
    $reviewed = app(ReviewKpiAssessmentAction::class)->execute($submitted->fresh(), [], $hrd);
    $rejected = app(RejectKpiAssessmentAction::class)->execute(createSubmittedWorkflowAssessment($manager, makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value)), ['notes' => 'Rejected'], $hrd);

    expect(fn () => app(LockKpiAssessmentAction::class)->execute($submitted, [], $approver))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(LockKpiAssessmentAction::class)->execute($reviewed, [], $approver))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(LockKpiAssessmentAction::class)->execute($rejected, [], $approver))
        ->toThrow(AuthorizationException::class);
});

it('locked assessment cannot be edited resubmitted or recalculated through update actions', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $hrd = makeApprovalWorkflowUser(SystemRole::HRD->value);
    $approver = makeApprovalWorkflowUser(SystemRole::APPROVER->value);
    $assessment = createSubmittedWorkflowAssessment($manager, $employee);
    $assessment = app(ReviewKpiAssessmentAction::class)->execute($assessment, [], $hrd);
    $assessment = app(ApproveKpiAssessmentAction::class)->execute($assessment, [], $approver);
    $assessment = app(LockKpiAssessmentAction::class)->execute($assessment, [], $approver);
    $firstItem = $assessment->items()->firstOrFail();

    expect(fn () => app(UpdateKpiAssessmentAction::class)->execute($assessment, ['notes' => 'Locked edit attempt'], $manager))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(SubmitKpiAssessmentAction::class)->execute($assessment, $manager))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(UpdateKpiAssessmentItemAction::class)->execute($firstItem, [
        'item_id' => $firstItem->getKey(),
        'score' => 1,
    ], $manager))->toThrow(AuthorizationException::class);

    expect(fn () => app(UpdateKpiAttendanceAdjustmentAction::class)->execute($assessment->attendanceAdjustment, [
        'working_days' => 25,
        'sick_days' => 0,
        'permission_days' => 0,
        'absent_days' => 0,
        'leave_days' => 0,
    ], $manager))->toThrow(AuthorizationException::class);
});

it('lock creates approval history and audit log', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $hrd = makeApprovalWorkflowUser(SystemRole::HRD->value);
    $approver = makeApprovalWorkflowUser(SystemRole::APPROVER->value);
    $assessment = createSubmittedWorkflowAssessment($manager, $employee);
    $assessment = app(ReviewKpiAssessmentAction::class)->execute($assessment, [], $hrd);
    $assessment = app(ApproveKpiAssessmentAction::class)->execute($assessment, [], $approver);

    app(LockKpiAssessmentAction::class)->execute($assessment, ['notes' => 'Locked after sign-off.'], $approver);

    $approval = KpiApproval::query()->where('kpi_assessment_id', $assessment->getKey())->latest('id')->first();

    expect($approval)->not->toBeNull()
        ->and($approval?->action)->toBe(KpiApprovalActionEnum::LOCKED)
        ->and($approval?->to_status)->toBe(KpiAssessmentStatus::LOCKED->value);

    $this->assertDatabaseHas('activity_log', [
        'event' => 'kpi_assessment.locked',
    ]);
});

it('manager cannot approve reject or lock', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $hrd = makeApprovalWorkflowUser(SystemRole::HRD->value);
    $assessment = createSubmittedWorkflowAssessment($manager, $employee);
    $reviewed = app(ReviewKpiAssessmentAction::class)->execute($assessment, [], $hrd);

    expect(fn () => app(ApproveKpiAssessmentAction::class)->execute($reviewed, [], $manager))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(RejectKpiAssessmentAction::class)->execute($reviewed, ['notes' => 'No access'], $manager))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(LockKpiAssessmentAction::class)->execute($reviewed, [], $manager))
        ->toThrow(AuthorizationException::class);
});

it('employee cannot approve reject or lock', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $hrd = makeApprovalWorkflowUser(SystemRole::HRD->value);
    $approver = makeApprovalWorkflowUser(SystemRole::APPROVER->value);
    $assessment = createSubmittedWorkflowAssessment($manager, $employee);
    $reviewed = app(ReviewKpiAssessmentAction::class)->execute($assessment, [], $hrd);
    $approved = app(ApproveKpiAssessmentAction::class)->execute($reviewed, [], $approver);

    expect(fn () => app(ApproveKpiAssessmentAction::class)->execute($reviewed->fresh(), [], $employee))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(RejectKpiAssessmentAction::class)->execute($reviewed->fresh(), ['notes' => 'No access'], $employee))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(LockKpiAssessmentAction::class)->execute($approved, [], $employee))
        ->toThrow(AuthorizationException::class);
});

it('manager cannot edit reviewed approved or locked assessment', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $hrd = makeApprovalWorkflowUser(SystemRole::HRD->value);
    $approver = makeApprovalWorkflowUser(SystemRole::APPROVER->value);
    $submitted = createSubmittedWorkflowAssessment($manager, $employee);
    $reviewed = app(ReviewKpiAssessmentAction::class)->execute($submitted, [], $hrd);
    $approved = app(ApproveKpiAssessmentAction::class)->execute($reviewed, [], $approver);
    $locked = app(LockKpiAssessmentAction::class)->execute($approved, [], $approver);

    expect($manager->can('update', $reviewed))->toBeFalse()
        ->and($manager->can('update', $approved))->toBeFalse()
        ->and($manager->can('update', $locked))->toBeFalse();
});

it('employee can view own assessment status only', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $otherEmployee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $assessment = createSubmittedWorkflowAssessment($manager, $employee);

    expect($employee->can('view', $assessment))->toBeTrue()
        ->and($otherEmployee->can('view', $assessment))->toBeFalse()
        ->and($employee->can('update', $assessment))->toBeFalse()
        ->and($employee->can('approve', $assessment))->toBeFalse()
        ->and($employee->can('reject', $assessment))->toBeFalse()
        ->and($employee->can('lock', $assessment))->toBeFalse();
});

it('approver cannot view draft assessment', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $approver = makeApprovalWorkflowUser(SystemRole::APPROVER->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeApprovalWorkflowAssignment($employee);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);

    expect($approver->can('view', $assessment))->toBeFalse();
});

it('approver cannot view submitted assessment', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $approver = makeApprovalWorkflowUser(SystemRole::APPROVER->value);
    $assessment = createSubmittedWorkflowAssessment($manager, $employee);

    expect($approver->can('view', $assessment))->toBeFalse();
});

it('approver can view reviewed approved and locked assessments', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $hrd = makeApprovalWorkflowUser(SystemRole::HRD->value);
    $approver = makeApprovalWorkflowUser(SystemRole::APPROVER->value);
    $submitted = createSubmittedWorkflowAssessment($manager, $employee);
    $reviewed = app(ReviewKpiAssessmentAction::class)->execute($submitted, [], $hrd);
    $approved = app(ApproveKpiAssessmentAction::class)->execute($reviewed, [], $approver);
    $locked = app(LockKpiAssessmentAction::class)->execute($approved, [], $approver);

    expect($approver->can('view', $reviewed->fresh()))->toBeTrue()
        ->and($approver->can('view', $approved->fresh()))->toBeTrue()
        ->and($approver->can('view', $locked->fresh()))->toBeTrue();
});

it('approver resource query excludes draft submitted and rejected assessments', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $hrd = makeApprovalWorkflowUser(SystemRole::HRD->value);
    $approver = makeApprovalWorkflowUser(SystemRole::APPROVER->value);

    $draftEmployee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $draftEmployee->update(['name' => 'Draft Employee']);
    $draftEmployee->update(['supervisor_id' => $manager->getKey()]);
    $draftAssignment = makeApprovalWorkflowAssignment($draftEmployee);
    $draftAssessment = app(CreateKpiAssessmentAction::class)->execute($draftAssignment, $manager);

    $submittedEmployee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $submittedEmployee->update(['name' => 'Submitted Employee']);
    $submittedAssessment = createSubmittedWorkflowAssessment($manager, $submittedEmployee);

    $reviewedEmployee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $reviewedEmployee->update(['name' => 'Reviewed Employee']);
    $reviewedAssessment = createSubmittedWorkflowAssessment($manager, $reviewedEmployee);
    $reviewedAssessment = app(ReviewKpiAssessmentAction::class)->execute($reviewedAssessment, [], $hrd);

    $approvedEmployee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $approvedEmployee->update(['name' => 'Approved Employee']);
    $approvedAssessment = createSubmittedWorkflowAssessment($manager, $approvedEmployee);
    $approvedAssessment = app(ReviewKpiAssessmentAction::class)->execute($approvedAssessment, [], $hrd);
    $approvedAssessment = app(ApproveKpiAssessmentAction::class)->execute($approvedAssessment, [], $approver);

    $lockedEmployee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $lockedEmployee->update(['name' => 'Locked Employee']);
    $lockedAssessment = createSubmittedWorkflowAssessment($manager, $lockedEmployee);
    $lockedAssessment = app(ReviewKpiAssessmentAction::class)->execute($lockedAssessment, [], $hrd);
    $lockedAssessment = app(ApproveKpiAssessmentAction::class)->execute($lockedAssessment, [], $approver);
    $lockedAssessment = app(LockKpiAssessmentAction::class)->execute($lockedAssessment, [], $approver);

    $rejectedEmployee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $rejectedEmployee->update(['name' => 'Rejected Employee']);
    $rejectedAssessment = createSubmittedWorkflowAssessment($manager, $rejectedEmployee);
    $rejectedAssessment = app(RejectKpiAssessmentAction::class)->execute($rejectedAssessment, ['notes' => 'Rejected'], $hrd);

    actingAs($approver);

    $visibleIds = KpiAssessmentResource::getEloquentQuery()
        ->pluck('kpi_assessments.id')
        ->all();

    expect($visibleIds)->not->toContain($draftAssessment->getKey(), $submittedAssessment->getKey(), $rejectedAssessment->getKey())
        ->and($visibleIds)->toContain($reviewedAssessment->getKey(), $approvedAssessment->getKey(), $lockedAssessment->getKey());

    get('/admin/kpi-assessments')
        ->assertOk()
        ->assertDontSee('Draft Employee')
        ->assertDontSee('Submitted Employee')
        ->assertDontSee('Rejected Employee');
});

it('approver navigation badge excludes draft submitted and rejected assessments', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $hrd = makeApprovalWorkflowUser(SystemRole::HRD->value);
    $approver = makeApprovalWorkflowUser(SystemRole::APPROVER->value);

    $draftEmployee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $draftEmployee->update(['supervisor_id' => $manager->getKey()]);
    $draftAssignment = makeApprovalWorkflowAssignment($draftEmployee);
    app(CreateKpiAssessmentAction::class)->execute($draftAssignment, $manager);

    $submittedEmployee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    createSubmittedWorkflowAssessment($manager, $submittedEmployee);

    $reviewedEmployee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $reviewedAssessment = createSubmittedWorkflowAssessment($manager, $reviewedEmployee);
    app(ReviewKpiAssessmentAction::class)->execute($reviewedAssessment, [], $hrd);

    $approvedEmployee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $approvedAssessment = createSubmittedWorkflowAssessment($manager, $approvedEmployee);
    $approvedAssessment = app(ReviewKpiAssessmentAction::class)->execute($approvedAssessment, [], $hrd);
    app(ApproveKpiAssessmentAction::class)->execute($approvedAssessment, [], $approver);

    $rejectedEmployee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $rejectedAssessment = createSubmittedWorkflowAssessment($manager, $rejectedEmployee);
    app(RejectKpiAssessmentAction::class)->execute($rejectedAssessment, ['notes' => 'Rejected'], $hrd);

    actingAs($approver);

    expect(KpiAssessmentResource::getNavigationBadge())->toBe('2');
});

it('manager and employee resource visibility remains scoped correctly', function () {
    $manager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $otherManager = makeApprovalWorkflowUser(SystemRole::MANAGER->value);
    $employee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $otherEmployee = makeApprovalWorkflowUser(SystemRole::EMPLOYEE->value);
    $assessment = createSubmittedWorkflowAssessment($manager, $employee);
    $otherAssessment = createSubmittedWorkflowAssessment($otherManager, $otherEmployee);

    actingAs($manager);
    $managerVisibleIds = KpiAssessmentResource::getEloquentQuery()
        ->pluck('kpi_assessments.id')
        ->all();

    expect($managerVisibleIds)->toContain($assessment->getKey())
        ->and($managerVisibleIds)->not->toContain($otherAssessment->getKey());

    actingAs($employee);
    $employeeVisibleIds = KpiAssessmentResource::getEloquentQuery()
        ->pluck('kpi_assessments.id')
        ->all();

    expect($employeeVisibleIds)->toContain($assessment->getKey())
        ->and($employeeVisibleIds)->not->toContain($otherAssessment->getKey());
});
