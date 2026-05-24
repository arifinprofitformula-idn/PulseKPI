<?php

use App\Actions\KpiAssessments\CreateKpiAssessmentAction;
use App\Actions\KpiAssignments\AssignKpiTemplateAction;
use App\Actions\KpiAssignments\BulkAssignKpiTemplateAction;
use App\Actions\KpiAssignments\CancelKpiAssignmentAction;
use App\Actions\KpiAssignments\ValidateKpiAssignmentAction;
use App\Enums\KpiAssignmentStatus;
use App\Enums\SystemRole;
use App\Events\KpiAssigned;
use App\Events\KpiAssignmentCancelled;
use App\Filament\Resources\KpiAssignments\KpiAssignmentResource;
use App\Models\Division;
use App\Models\KpiPeriod;
use App\Models\KpiScoreRule;
use App\Models\KpiTemplate;
use App\Models\KpiTemplateItem;
use App\Models\User;
use App\Policies\KpiAssignmentPolicy;
use App\Policies\KpiPeriodPolicy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function makePublishedTemplate(): KpiTemplate
{
    $template = KpiTemplate::factory()->create([
        'is_active' => true,
        'published_at' => null,
    ]);

    $item = KpiTemplateItem::factory()->create([
        'kpi_template_id' => $template->getKey(),
        'weight' => '100.00',
    ]);

    foreach ([0, 1, 2] as $score) {
        KpiScoreRule::factory()->create([
            'kpi_template_item_id' => $item->getKey(),
            'score' => $score,
        ]);
    }

    $template->updateQuietly(['published_at' => now()]);

    return $template->fresh();
}

function makeActivePeriod(): KpiPeriod
{
    return KpiPeriod::factory()->yearly()->create(['is_active' => true]);
}

// ─── HRD can assign published active template ─────────────────────────────────

it('hrd can assign a published active template to an employee', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);
    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    $action = app(AssignKpiTemplateAction::class);
    $assignment = $action->execute($period, $template, $employee, $hrd);

    expect($assignment->status)->toBe(KpiAssignmentStatus::ASSIGNED)
        ->and($assignment->employee_id)->toBe($employee->getKey())
        ->and($assignment->kpi_period_id)->toBe($period->getKey())
        ->and($assignment->kpi_template_id)->toBe($template->getKey());
});

it('hrd can assign a published active template to a Supervisor', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $supervisor = makeUser(SystemRole::SUPERVISOR->value);
    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    $assignment = app(AssignKpiTemplateAction::class)->execute($period, $template, $supervisor, $hrd);

    expect($assignment->status)->toBe(KpiAssignmentStatus::ASSIGNED)
        ->and($assignment->employee_id)->toBe($supervisor->getKey());
});

// ─── HRD cannot assign draft template ────────────────────────────────────────

it('hrd cannot assign a draft template', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);
    $period = makeActivePeriod();
    $template = KpiTemplate::factory()->create(['is_active' => true, 'published_at' => null]);

    $action = app(AssignKpiTemplateAction::class);

    expect(fn () => $action->execute($period, $template, $employee, $hrd))
        ->toThrow(ValidationException::class);
});

// ─── HRD cannot assign inactive template ─────────────────────────────────────

it('hrd cannot assign an inactive template', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);
    $period = makeActivePeriod();
    $template = KpiTemplate::factory()->create(['is_active' => false, 'published_at' => now()]);

    $action = app(AssignKpiTemplateAction::class);

    expect(fn () => $action->execute($period, $template, $employee, $hrd))
        ->toThrow(ValidationException::class);
});

// ─── Duplicate assignment is rejected ────────────────────────────────────────

it('duplicate assignment for same period and employee is rejected', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);
    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    $action = app(AssignKpiTemplateAction::class);
    $action->execute($period, $template, $employee, $hrd);

    expect(fn () => $action->execute($period, $template, $employee, $hrd))
        ->toThrow(ValidationException::class);
});

it('kpi assignment to Supervisor passes validation', function () {
    $supervisor = makeUser(SystemRole::SUPERVISOR->value);
    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    $resolved = app(ValidateKpiAssignmentAction::class)
        ->execute($period, $template, $supervisor);

    expect($resolved['employee']->is($supervisor))->toBeTrue();
});

it('kpi assignment to hrd super admin approver and manager fails validation', function (string $role) {
    $user = makeUser($role);
    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    expect(fn () => app(ValidateKpiAssignmentAction::class)
        ->execute($period, $template, $user))
        ->toThrow(ValidationException::class);
})->with([
    SystemRole::HRD->value,
    SystemRole::SUPER_ADMIN->value,
    SystemRole::APPROVER->value,
    SystemRole::MANAGER->value,
]);

// ─── assigned_at and assigned_by are set ─────────────────────────────────────

it('assigned_at and assigned_by are set on assignment', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);
    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    $action = app(AssignKpiTemplateAction::class);
    $assignment = $action->execute($period, $template, $employee, $hrd);

    expect($assignment->assigned_at)->not->toBeNull()
        ->and($assignment->assigned_by)->toBe($hrd->getKey());
});

// ─── Manager can view direct subordinate assignment ───────────────────────────

it('manager can view direct subordinate assignment', function () {
    $manager = makeUser(SystemRole::MANAGER->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);

    $period = makeActivePeriod();
    $template = makePublishedTemplate();
    $hrd = makeUser(SystemRole::HRD->value);

    $action = app(AssignKpiTemplateAction::class);
    $assignment = $action->execute($period, $template, $employee, $hrd);

    test()->actingAs($manager);

    $this->assertTrue($manager->manages($employee));

    $policy = new KpiAssignmentPolicy;
    expect($policy->view($manager, $assignment))->toBeTrue();
});

it('manager can see assignment of a direct Supervisor report', function () {
    $manager = makeUser(SystemRole::MANAGER->value);
    $supervisor = makeUser(SystemRole::SUPERVISOR->value);
    $supervisor->update(['supervisor_id' => $manager->getKey()]);
    $period = makeActivePeriod();
    $template = makePublishedTemplate();
    $hrd = makeUser(SystemRole::HRD->value);

    $assignment = app(AssignKpiTemplateAction::class)->execute($period, $template, $supervisor, $hrd);

    test()->actingAs($manager);

    expect(KpiAssignmentResource::canViewAny())->toBeTrue()
        ->and(KpiAssignmentResource::canView($assignment))->toBeTrue()
        ->and(KpiAssignmentResource::getEloquentQuery()->pluck('kpi_assignments.id')->all())
        ->toContain($assignment->getKey());
});

// ─── Manager cannot view unrelated employee assignment ────────────────────────

it('manager cannot view assignment of unrelated employee', function () {
    $manager = makeUser(SystemRole::MANAGER->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);

    $period = makeActivePeriod();
    $template = makePublishedTemplate();
    $hrd = makeUser(SystemRole::HRD->value);

    $action = app(AssignKpiTemplateAction::class);
    $assignment = $action->execute($period, $template, $employee, $hrd);

    $policy = new KpiAssignmentPolicy;
    expect($policy->view($manager, $assignment))->toBeFalse();
});

// ─── Employee can view own assignment ─────────────────────────────────────────

it('employee can view their own assignment', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);

    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    $action = app(AssignKpiTemplateAction::class);
    $assignment = $action->execute($period, $template, $employee, $hrd);

    $policy = new KpiAssignmentPolicy;
    expect($policy->view($employee, $assignment))->toBeTrue();
});

it('supervisor can see assignments of direct employee staff only', function () {
    $supervisor = makeUser(SystemRole::SUPERVISOR->value);
    $otherSupervisor = makeUser(SystemRole::SUPERVISOR->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);
    $otherEmployee = makeUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $supervisor->getKey()]);
    $otherEmployee->update(['supervisor_id' => $otherSupervisor->getKey()]);
    $period = makeActivePeriod();
    $template = makePublishedTemplate();
    $hrd = makeUser(SystemRole::HRD->value);

    $visibleAssignment = app(AssignKpiTemplateAction::class)->execute($period, $template, $employee, $hrd);
    $hiddenAssignment = app(AssignKpiTemplateAction::class)->execute(
        KpiPeriod::factory()->yearly()->create(['is_active' => true]),
        makePublishedTemplate(),
        $otherEmployee,
        $hrd
    );

    test()->actingAs($supervisor);

    $visibleIds = KpiAssignmentResource::getEloquentQuery()
        ->pluck('kpi_assignments.id')
        ->all();

    expect(KpiAssignmentResource::canViewAny())->toBeTrue()
        ->and(KpiAssignmentResource::canView($visibleAssignment))->toBeTrue()
        ->and(KpiAssignmentResource::canView($hiddenAssignment))->toBeFalse()
        ->and($visibleIds)->toContain($visibleAssignment->getKey())
        ->and($visibleIds)->not->toContain($hiddenAssignment->getKey());
});

// ─── Employee cannot view another employee's assignment ───────────────────────

it('employee cannot view another employee assignment', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $employee1 = makeUser(SystemRole::EMPLOYEE->value);
    $employee2 = makeUser(SystemRole::EMPLOYEE->value);

    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    $action = app(AssignKpiTemplateAction::class);
    $assignment = $action->execute($period, $template, $employee1, $hrd);

    $policy = new KpiAssignmentPolicy;
    expect($policy->view($employee2, $assignment))->toBeFalse();
});

// ─── Employee cannot create assignment ────────────────────────────────────────

it('employee cannot create kpi assignment', function () {
    $employee = makeUser(SystemRole::EMPLOYEE->value);

    test()->actingAs($employee);

    $this->get('/admin/kpi-assignments/create')->assertForbidden();
});

// ─── HRD can cancel assignment ────────────────────────────────────────────────

it('hrd can cancel a kpi assignment', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);
    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    $assignAction = app(AssignKpiTemplateAction::class);
    $assignment = $assignAction->execute($period, $template, $employee, $hrd);

    $cancelAction = app(CancelKpiAssignmentAction::class);
    $cancelled = $cancelAction->execute($assignment, $hrd);

    expect($cancelled->status)->toBe(KpiAssignmentStatus::CANCELLED);
});

// ─── cancelled_at is set when cancelled ───────────────────────────────────────

it('cancelled_at is set when assignment is cancelled', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);
    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    $assignAction = app(AssignKpiTemplateAction::class);
    $assignment = $assignAction->execute($period, $template, $employee, $hrd);

    $cancelAction = app(CancelKpiAssignmentAction::class);
    $cancelled = $cancelAction->execute($assignment, $hrd);

    expect($cancelled->cancelled_at)->not->toBeNull();
});

// ─── Bulk assignment assigns eligible employees ───────────────────────────────

it('bulk assignment assigns all eligible employees in a division', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $division = Division::factory()->create();
    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    $employees = User::factory()->count(3)->create(['division_id' => $division->getKey()]);

    foreach ($employees as $employee) {
        $employee->assignRole(SystemRole::EMPLOYEE->value);
    }

    $action = app(BulkAssignKpiTemplateAction::class);
    $summary = $action->execute([
        'kpi_period_id' => $period->getKey(),
        'kpi_template_id' => $template->getKey(),
        'division_id' => $division->getKey(),
    ], $hrd);

    expect($summary['assigned_count'])->toBe(3)
        ->and($summary['skipped_duplicate_count'])->toBe(0)
        ->and($summary['total_candidates'])->toBe(3);
});

// ─── Bulk assignment skips duplicates ─────────────────────────────────────────

it('bulk assignment skips employees already assigned', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $division = Division::factory()->create();
    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    $employees = User::factory()->count(3)->create(['division_id' => $division->getKey()]);

    foreach ($employees as $employee) {
        $employee->assignRole(SystemRole::EMPLOYEE->value);
    }

    $action = app(BulkAssignKpiTemplateAction::class);

    $action->execute([
        'kpi_period_id' => $period->getKey(),
        'kpi_template_id' => $template->getKey(),
        'division_id' => $division->getKey(),
    ], $hrd);

    $summary = $action->execute([
        'kpi_period_id' => $period->getKey(),
        'kpi_template_id' => $template->getKey(),
        'division_id' => $division->getKey(),
    ], $hrd);

    expect($summary['skipped_duplicate_count'])->toBe(3)
        ->and($summary['assigned_count'])->toBe(0);
});

// ─── Audit log for single assignment ─────────────────────────────────────────

it('audit log is created for single assignment', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);
    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    test()->actingAs($hrd);

    $action = app(AssignKpiTemplateAction::class);
    $action->execute($period, $template, $employee, $hrd);

    $this->assertDatabaseHas('activity_log', [
        'event' => 'kpi_assignment.assigned',
    ]);
});

// ─── Audit log for cancellation ───────────────────────────────────────────────

it('audit log is created for assignment cancellation', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);
    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    test()->actingAs($hrd);

    $assignAction = app(AssignKpiTemplateAction::class);
    $assignment = $assignAction->execute($period, $template, $employee, $hrd);

    $cancelAction = app(CancelKpiAssignmentAction::class);
    $cancelAction->execute($assignment, $hrd);

    $this->assertDatabaseHas('activity_log', [
        'event' => 'kpi_assignment.cancelled',
    ]);
});

// ─── Audit log for bulk assignment ────────────────────────────────────────────

it('audit log is created for bulk assignment', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    makeUser(SystemRole::EMPLOYEE->value);

    test()->actingAs($hrd);

    $action = app(BulkAssignKpiTemplateAction::class);
    $action->execute([
        'kpi_period_id' => $period->getKey(),
        'kpi_template_id' => $template->getKey(),
    ], $hrd);

    $this->assertDatabaseHas('activity_log', [
        'event' => 'kpi_assignment.bulk_assigned',
    ]);
});

// ─── KpiAssigned event is dispatched ──────────────────────────────────────────

it('KpiAssigned event is dispatched when assignment is created', function () {
    Event::fake([KpiAssigned::class]);

    $hrd = makeUser(SystemRole::HRD->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);
    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    $action = app(AssignKpiTemplateAction::class);
    $action->execute($period, $template, $employee, $hrd);

    Event::assertDispatched(KpiAssigned::class);
});

// ─── KpiAssignmentCancelled event is dispatched ──────────────────────────────

it('KpiAssignmentCancelled event is dispatched when assignment is cancelled', function () {
    Event::fake([KpiAssignmentCancelled::class]);

    $hrd = makeUser(SystemRole::HRD->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);
    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    $assignAction = app(AssignKpiTemplateAction::class);
    $assignment = $assignAction->execute($period, $template, $employee, $hrd);

    $cancelAction = app(CancelKpiAssignmentAction::class);
    $cancelAction->execute($assignment, $hrd);

    Event::assertDispatched(KpiAssignmentCancelled::class);
});

// ─── Inactive period cannot be assigned ──────────────────────────────────────

it('hrd cannot assign to an inactive period', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);
    $period = KpiPeriod::factory()->yearly()->create(['is_active' => false]);
    $template = makePublishedTemplate();

    $action = app(AssignKpiTemplateAction::class);

    expect(fn () => $action->execute($period, $template, $employee, $hrd))
        ->toThrow(ValidationException::class);
});

// ─── Double cancel prevention ────────────────────────────────────────────────

it('cancelling an already-cancelled assignment throws a validation exception', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);
    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    $assignAction = app(AssignKpiTemplateAction::class);
    $assignment = $assignAction->execute($period, $template, $employee, $hrd);

    $cancelAction = app(CancelKpiAssignmentAction::class);
    $cancelAction->execute($assignment, $hrd);

    // Call without actor to bypass the Gate and hit the explicit status guard.
    expect(fn () => $cancelAction->execute($assignment->fresh()))
        ->toThrow(ValidationException::class);
});

// ─── Manager cannot create assignment ────────────────────────────────────────

it('manager cannot create kpi assignment', function () {
    $manager = makeUser(SystemRole::MANAGER->value);

    test()->actingAs($manager);

    $this->get('/admin/kpi-assignments/create')->assertForbidden();
});

// ─── Manager cannot cancel assignment ────────────────────────────────────────

it('manager cannot cancel kpi assignment', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $manager = makeUser(SystemRole::MANAGER->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);
    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    $action = app(AssignKpiTemplateAction::class);
    $assignment = $action->execute($period, $template, $employee, $hrd);

    $policy = new KpiAssignmentPolicy;
    expect($policy->cancel($manager, $assignment))->toBeFalse();
});

// ─── Period with assignments cannot be deleted by HRD ────────────────────────

it('period with assignments cannot be deleted by hrd', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);
    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    $action = app(AssignKpiTemplateAction::class);
    $action->execute($period, $template, $employee, $hrd);

    $policy = new KpiPeriodPolicy;
    expect($policy->delete($hrd, $period->fresh()))->toBeFalse();
});

// ─── Period without assignments can be deleted by HRD ────────────────────────

it('period without assignments can be deleted by hrd', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $period = makeActivePeriod();

    $policy = new KpiPeriodPolicy;
    expect($policy->delete($hrd, $period))->toBeTrue();
});

// ─── Cancellation blocked when assessment exists ──────────────────────────────

it('hrd cannot cancel assignment when an assessment already exists', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $manager = makeUser(SystemRole::MANAGER->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    $assignment = app(AssignKpiTemplateAction::class)->execute($period, $template, $employee, $hrd);

    test()->actingAs($manager);
    app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);

    expect(fn () => app(CancelKpiAssignmentAction::class)->execute($assignment->fresh(), $hrd))
        ->toThrow(ValidationException::class);
});

it('assignment status remains assigned when cancellation is blocked by existing assessment', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $manager = makeUser(SystemRole::MANAGER->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    $assignment = app(AssignKpiTemplateAction::class)->execute($period, $template, $employee, $hrd);

    test()->actingAs($manager);
    app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);

    try {
        app(CancelKpiAssignmentAction::class)->execute($assignment->fresh(), $hrd);
    } catch (ValidationException) {
    }

    expect($assignment->fresh()->status)->toBe(KpiAssignmentStatus::ASSIGNED);
});

it('assessment record remains intact when cancellation is blocked', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $manager = makeUser(SystemRole::MANAGER->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    $assignment = app(AssignKpiTemplateAction::class)->execute($period, $template, $employee, $hrd);

    test()->actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);

    try {
        app(CancelKpiAssignmentAction::class)->execute($assignment->fresh(), $hrd);
    } catch (ValidationException) {
    }

    $this->assertDatabaseHas('kpi_assessments', [
        'id' => $assessment->getKey(),
        'kpi_assignment_id' => $assignment->getKey(),
    ]);
});

it('hrd can still cancel an assignment that has no assessment', function () {
    $hrd = makeUser(SystemRole::HRD->value);
    $employee = makeUser(SystemRole::EMPLOYEE->value);
    $period = makeActivePeriod();
    $template = makePublishedTemplate();

    $assignment = app(AssignKpiTemplateAction::class)->execute($period, $template, $employee, $hrd);

    $cancelled = app(CancelKpiAssignmentAction::class)->execute($assignment, $hrd);

    expect($cancelled->status)->toBe(KpiAssignmentStatus::CANCELLED);
});
