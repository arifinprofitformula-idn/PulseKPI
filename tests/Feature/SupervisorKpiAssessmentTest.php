<?php

use App\Actions\KpiAssessments\CreateKpiAssessmentAction;
use App\Actions\KpiAssessments\ReviewKpiAssessmentAction;
use App\Actions\KpiAssessments\SubmitKpiAssessmentAction;
use App\Actions\KpiAssessments\UpdateKpiAssessmentAction;
use App\Actions\KpiAssessments\UpdateKpiAssessmentItemAction;
use App\Enums\KpiAssessmentStatus;
use App\Enums\KpiAssignmentStatus;
use App\Enums\SystemRole;
use App\Filament\Resources\KpiAssessments\KpiAssessmentResource;
use App\Models\KpiAssignment;
use App\Models\KpiPeriod;
use App\Models\KpiTemplate;
use App\Models\KpiTemplateItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makeSupervisorAssessmentUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function makeSupervisorAssessmentTemplate(): KpiTemplate
{
    $template = KpiTemplate::factory()->create([
        'is_active' => true,
        'published_at' => null,
    ]);

    KpiTemplateItem::factory()->create([
        'kpi_template_id' => $template->getKey(),
        'weight' => '100.00',
        'is_required' => true,
    ]);

    $template->updateQuietly([
        'published_at' => now(),
    ]);

    return $template->fresh('items');
}

function makeSupervisorAssessmentAssignment(User $employee): KpiAssignment
{
    $period = KpiPeriod::factory()->yearly()->create(['is_active' => true]);
    $template = makeSupervisorAssessmentTemplate();

    return KpiAssignment::factory()->create([
        'kpi_period_id' => $period->getKey(),
        'kpi_template_id' => $template->getKey(),
        'employee_id' => $employee->getKey(),
        'status' => KpiAssignmentStatus::ASSIGNED->value,
        'assigned_at' => now(),
    ]);
}

it('Supervisor can create assessment for direct Employee staff', function () {
    $supervisor = makeSupervisorAssessmentUser(SystemRole::SUPERVISOR->value);
    $employee = makeSupervisorAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $supervisor->getKey()]);
    $assignment = makeSupervisorAssessmentAssignment($employee);

    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $supervisor);

    expect($assessment->assessor_id)->toBe($supervisor->getKey())
        ->and($assessment->employee_id)->toBe($employee->getKey())
        ->and($assessment->status)->toBe(KpiAssessmentStatus::DRAFT);
});

it('Supervisor can update and submit draft assessment for direct Employee staff', function () {
    $supervisor = makeSupervisorAssessmentUser(SystemRole::SUPERVISOR->value);
    $employee = makeSupervisorAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $supervisor->getKey()]);
    $assignment = makeSupervisorAssessmentAssignment($employee);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $supervisor);

    app(UpdateKpiAssessmentAction::class)->execute($assessment, ['notes' => 'Supervisor draft'], $supervisor);

    foreach ($assessment->items as $item) {
        app(UpdateKpiAssessmentItemAction::class)->execute($item, [
            'item_id' => $item->getKey(),
            'score' => 2,
        ], $supervisor);
    }

    $submitted = app(SubmitKpiAssessmentAction::class)->execute($assessment->fresh('items.templateItem', 'attendanceAdjustment', 'assignment'), $supervisor);

    expect($submitted->status)->toBe(KpiAssessmentStatus::SUBMITTED)
        ->and($submitted->submitted_at)->not->toBeNull();
});

it('Supervisor can revise rejected assessment for direct Employee staff', function () {
    $supervisor = makeSupervisorAssessmentUser(SystemRole::SUPERVISOR->value);
    $employee = makeSupervisorAssessmentUser(SystemRole::EMPLOYEE->value);
    $hrd = makeSupervisorAssessmentUser(SystemRole::HRD->value);
    $employee->update(['supervisor_id' => $supervisor->getKey()]);
    $assignment = makeSupervisorAssessmentAssignment($employee);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $supervisor);

    foreach ($assessment->items as $item) {
        app(UpdateKpiAssessmentItemAction::class)->execute($item, [
            'item_id' => $item->getKey(),
            'score' => 2,
        ], $supervisor);
    }

    $assessment = app(SubmitKpiAssessmentAction::class)->execute($assessment->fresh('items.templateItem', 'attendanceAdjustment', 'assignment'), $supervisor);
    $assessment->forceFill([
        'status' => KpiAssessmentStatus::REJECTED,
        'rejected_at' => now(),
    ])->saveQuietly();

    app(UpdateKpiAssessmentAction::class)->execute($assessment->fresh(), ['notes' => 'Supervisor revision'], $supervisor);

    expect($assessment->fresh()->notes)->toBe('Supervisor revision');
});

it('Supervisor cannot assess Employee under another Supervisor', function () {
    $supervisor = makeSupervisorAssessmentUser(SystemRole::SUPERVISOR->value);
    $otherSupervisor = makeSupervisorAssessmentUser(SystemRole::SUPERVISOR->value);
    $employee = makeSupervisorAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $otherSupervisor->getKey()]);
    $assignment = makeSupervisorAssessmentAssignment($employee);

    expect(fn () => app(CreateKpiAssessmentAction::class)->execute($assignment, $supervisor))
        ->toThrow(AuthorizationException::class);
});

it('Supervisor cannot assess self manager hrd super admin or approver', function (string $role) {
    $supervisor = makeSupervisorAssessmentUser(SystemRole::SUPERVISOR->value);
    $target = $role === SystemRole::SUPERVISOR->value
        ? $supervisor
        : makeSupervisorAssessmentUser($role);

    $assignment = makeSupervisorAssessmentAssignment($target);

    expect(fn () => app(CreateKpiAssessmentAction::class)->execute($assignment, $supervisor))
        ->toThrow(AuthorizationException::class);
})->with([
    SystemRole::SUPERVISOR->value,
    SystemRole::MANAGER->value,
    SystemRole::HRD->value,
    SystemRole::SUPER_ADMIN->value,
    SystemRole::APPROVER->value,
]);

it('Supervisor cannot edit locked assessment', function () {
    $supervisor = makeSupervisorAssessmentUser(SystemRole::SUPERVISOR->value);
    $employee = makeSupervisorAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $supervisor->getKey()]);
    $assignment = makeSupervisorAssessmentAssignment($employee);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $supervisor);
    $assessment->forceFill([
        'status' => KpiAssessmentStatus::LOCKED,
        'locked_at' => now(),
    ])->saveQuietly();

    expect(fn () => app(UpdateKpiAssessmentAction::class)->execute($assessment->fresh(), ['notes' => 'Locked'], $supervisor))
        ->toThrow(AuthorizationException::class);
});

it('Supervisor cannot review approve or lock assessment', function () {
    $supervisor = makeSupervisorAssessmentUser(SystemRole::SUPERVISOR->value);
    $employee = makeSupervisorAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $supervisor->getKey()]);
    $assignment = makeSupervisorAssessmentAssignment($employee);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $supervisor);
    $assessment->forceFill(['status' => KpiAssessmentStatus::SUBMITTED, 'submitted_at' => now()])->saveQuietly();

    expect(fn () => app(ReviewKpiAssessmentAction::class)->execute($assessment->fresh(), [], $supervisor))
        ->toThrow(AuthorizationException::class)
        ->and($supervisor->can('approve', $assessment->fresh()))->toBeFalse()
        ->and($supervisor->can('lock', $assessment->fresh()))->toBeFalse();
});

it('Manager can create update and submit assessment for direct Supervisor', function () {
    $manager = makeSupervisorAssessmentUser(SystemRole::MANAGER->value);
    $supervisor = makeSupervisorAssessmentUser(SystemRole::SUPERVISOR->value);
    $supervisor->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeSupervisorAssessmentAssignment($supervisor);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);

    app(UpdateKpiAssessmentAction::class)->execute($assessment, ['notes' => 'Manager on Supervisor'], $manager);

    foreach ($assessment->items as $item) {
        app(UpdateKpiAssessmentItemAction::class)->execute($item, [
            'item_id' => $item->getKey(),
            'score' => 2,
        ], $manager);
    }

    $submitted = app(SubmitKpiAssessmentAction::class)->execute($assessment->fresh('items.templateItem', 'attendanceAdjustment', 'assignment'), $manager);

    expect($submitted->status)->toBe(KpiAssessmentStatus::SUBMITTED)
        ->and($submitted->assessor_id)->toBe($manager->getKey());
});

it('Manager cannot assess unrelated Supervisor or self', function () {
    $manager = makeSupervisorAssessmentUser(SystemRole::MANAGER->value);
    $otherManager = makeSupervisorAssessmentUser(SystemRole::MANAGER->value);
    $supervisor = makeSupervisorAssessmentUser(SystemRole::SUPERVISOR->value);
    $supervisor->update(['supervisor_id' => $otherManager->getKey()]);

    $unrelatedAssignment = makeSupervisorAssessmentAssignment($supervisor);
    $selfAssignment = makeSupervisorAssessmentAssignment($manager);

    expect(fn () => app(CreateKpiAssessmentAction::class)->execute($unrelatedAssignment, $manager))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => app(CreateKpiAssessmentAction::class)->execute($selfAssignment, $manager))
        ->toThrow(AuthorizationException::class);
});

it('Supervisor resource visibility stays limited to direct staff and own assessed rows', function () {
    $supervisor = makeSupervisorAssessmentUser(SystemRole::SUPERVISOR->value);
    $otherSupervisor = makeSupervisorAssessmentUser(SystemRole::SUPERVISOR->value);
    $employee = makeSupervisorAssessmentUser(SystemRole::EMPLOYEE->value);
    $otherEmployee = makeSupervisorAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $supervisor->getKey()]);
    $otherEmployee->update(['supervisor_id' => $otherSupervisor->getKey()]);

    $visibleAssessment = app(CreateKpiAssessmentAction::class)->execute(makeSupervisorAssessmentAssignment($employee), $supervisor);
    $hiddenAssessment = app(CreateKpiAssessmentAction::class)->execute(makeSupervisorAssessmentAssignment($otherEmployee), $otherSupervisor);

    test()->actingAs($supervisor);

    $visibleIds = KpiAssessmentResource::getEloquentQuery()
        ->pluck('kpi_assessments.id')
        ->all();

    expect($visibleIds)->toContain($visibleAssessment->getKey())
        ->and($visibleIds)->not->toContain($hiddenAssessment->getKey());
});
