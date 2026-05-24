<?php

use App\Actions\KpiAssessments\ApproveKpiAssessmentAction;
use App\Actions\KpiAssessments\CreateKpiAssessmentAction;
use App\Actions\KpiAssessments\LockKpiAssessmentAction;
use App\Actions\KpiAssessments\RejectKpiAssessmentAction;
use App\Actions\KpiAssessments\ReviewKpiAssessmentAction;
use App\Actions\KpiAssessments\SubmitKpiAssessmentAction;
use App\Actions\KpiAssessments\UpdateKpiAssessmentAction;
use App\Actions\KpiAssessments\UpdateKpiAssessmentItemAction;
use App\Actions\KpiAssignments\AssignKpiTemplateAction;
use App\Enums\KpiAssessmentStatus;
use App\Enums\SystemRole;
use App\Filament\Resources\KpiAssessmentReports\KpiAssessmentReportResource;
use App\Filament\Resources\KpiAssessments\KpiAssessmentResource;
use App\Filament\Resources\KpiAssignments\KpiAssignmentResource;
use App\Filament\Resources\KpiReportExports\KpiReportExportResource;
use App\Models\Department;
use App\Models\Division;
use App\Models\KpiAssessment;
use App\Models\KpiAssignment;
use App\Models\KpiPeriod;
use App\Models\KpiReportExport;
use App\Models\KpiTemplate;
use App\Models\KpiTemplateItem;
use App\Models\Position;
use App\Models\User;
use App\Policies\KpiAssignmentPolicy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('local');
});

function makeSupervisorSecurityUser(string $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->assignRole($role);

    return $user;
}

/**
 * @return array{division: Division, department: Department, position: Position}
 */
function makeSupervisorSecurityUnit(string $name): array
{
    $division = Division::factory()->create(['name' => "{$name} Division"]);
    $department = Department::factory()->create([
        'division_id' => $division->getKey(),
        'name' => "{$name} Department",
    ]);
    $position = Position::factory()->create([
        'department_id' => $department->getKey(),
        'name' => "{$name} Position",
    ]);

    return compact('division', 'department', 'position');
}

function makeSupervisorSecuritySubject(string $name, string $role, ?User $supervisor = null, ?array $unit = null): User
{
    $unit ??= makeSupervisorSecurityUnit($name);

    return makeSupervisorSecurityUser($role, [
        'name' => $name,
        'division_id' => $unit['division']->getKey(),
        'department_id' => $unit['department']->getKey(),
        'position_id' => $unit['position']->getKey(),
        'supervisor_id' => $supervisor?->getKey(),
    ]);
}

function makeSupervisorSecurityTemplate(string $name = 'Supervisor Security Template'): KpiTemplate
{
    $template = KpiTemplate::factory()->create([
        'name' => $name,
        'is_active' => true,
        'published_at' => null,
    ]);

    KpiTemplateItem::factory()->create([
        'kpi_template_id' => $template->getKey(),
        'weight' => '100.00',
        'is_required' => true,
    ]);

    $template->updateQuietly(['published_at' => now()]);

    return $template->fresh('items');
}

function makeSupervisorSecurityPeriod(string $name = 'Supervisor Security Period'): KpiPeriod
{
    return KpiPeriod::factory()->yearly()->create([
        'name' => $name,
        'is_active' => true,
    ]);
}

function assignSupervisorSecurityKpi(User $employee, User $assignedBy, ?KpiPeriod $period = null, ?KpiTemplate $template = null): KpiAssignment
{
    $period ??= makeSupervisorSecurityPeriod();
    $template ??= makeSupervisorSecurityTemplate();

    return app(AssignKpiTemplateAction::class)->execute($period, $template, $employee, $assignedBy);
}

function scoreAndSubmitSupervisorSecurityAssessment(KpiAssessment $assessment, User $actor): KpiAssessment
{
    app(UpdateKpiAssessmentAction::class)->execute($assessment, ['notes' => 'Security regression draft'], $actor);

    foreach ($assessment->items as $item) {
        app(UpdateKpiAssessmentItemAction::class)->execute($item, [
            'item_id' => $item->getKey(),
            'score' => 2,
        ], $actor);
    }

    return app(SubmitKpiAssessmentAction::class)->execute(
        $assessment->fresh('items.templateItem', 'attendanceAdjustment', 'assignment'),
        $actor,
    );
}

it('enforces the supervisor admin and dashboard access matrix', function () {
    $supervisor = makeSupervisorSecurityUser(SystemRole::SUPERVISOR->value);
    $employee = makeSupervisorSecurityUser(SystemRole::EMPLOYEE->value);
    $manager = makeSupervisorSecurityUser(SystemRole::MANAGER->value);
    $hrd = makeSupervisorSecurityUser(SystemRole::HRD->value);
    $approver = makeSupervisorSecurityUser(SystemRole::APPROVER->value);
    $superAdmin = makeSupervisorSecurityUser(SystemRole::SUPER_ADMIN->value);

    actingAs($supervisor);
    get('/admin')->assertRedirect('/admin/supervisor-dashboard');
    get('/admin/supervisor-dashboard')->assertOk();
    get('/admin/manager-dashboard')->assertForbidden();
    get('/admin/hrd-dashboard')->assertForbidden();
    get('/admin/approver-dashboard')->assertForbidden();

    actingAs($employee);
    get('/admin/supervisor-dashboard')->assertForbidden();
    get('/admin/kpi-assessments')->assertForbidden();

    actingAs($manager);
    get('/admin/supervisor-dashboard')->assertForbidden();

    actingAs($hrd);
    get('/admin/supervisor-dashboard')->assertForbidden();

    actingAs($approver);
    get('/admin/supervisor-dashboard')->assertForbidden();

    actingAs($superAdmin);
    get('/admin/supervisor-dashboard')->assertOk();
});

it('keeps supervisor assignment scope limited to direct staff and preserves manager and employee scope', function () {
    $hrd = makeSupervisorSecurityUser(SystemRole::HRD->value);
    $manager = makeSupervisorSecurityUser(SystemRole::MANAGER->value);
    $supervisor = makeSupervisorSecurityUser(SystemRole::SUPERVISOR->value, ['supervisor_id' => $manager->getKey()]);
    $otherSupervisor = makeSupervisorSecurityUser(SystemRole::SUPERVISOR->value, ['supervisor_id' => $manager->getKey()]);
    $nestedSupervisor = makeSupervisorSecurityUser(SystemRole::SUPERVISOR->value, ['supervisor_id' => $supervisor->getKey()]);
    $unit = makeSupervisorSecurityUnit('Security');

    $directEmployee = makeSupervisorSecuritySubject('Direct Staff', SystemRole::EMPLOYEE->value, $supervisor, $unit);
    $otherEmployee = makeSupervisorSecuritySubject('Other Staff', SystemRole::EMPLOYEE->value, $otherSupervisor, $unit);

    $employeeAssignment = assignSupervisorSecurityKpi($directEmployee, $hrd);
    $otherEmployeeAssignment = assignSupervisorSecurityKpi($otherEmployee, $hrd);
    $supervisorAssignment = assignSupervisorSecurityKpi($supervisor, $hrd);
    $nestedSupervisorAssignment = assignSupervisorSecurityKpi($nestedSupervisor, $hrd);

    actingAs($supervisor);

    expect(KpiAssignmentResource::canViewAny())->toBeTrue()
        ->and(KpiAssignmentResource::canView($employeeAssignment))->toBeTrue()
        ->and(KpiAssignmentResource::canView($otherEmployeeAssignment))->toBeFalse()
        ->and(KpiAssignmentResource::canView($supervisorAssignment))->toBeFalse()
        ->and(KpiAssignmentResource::canView($nestedSupervisorAssignment))->toBeFalse()
        ->and((new KpiAssignmentPolicy)->viewAny($supervisor))->toBeTrue()
        ->and((new KpiAssignmentPolicy)->view($supervisor, $employeeAssignment))->toBeTrue();

    $visibleIds = KpiAssignmentResource::getEloquentQuery()->pluck('kpi_assignments.id')->all();

    expect($visibleIds)->toContain($employeeAssignment->getKey())
        ->and($visibleIds)->not->toContain($otherEmployeeAssignment->getKey())
        ->and($visibleIds)->not->toContain($supervisorAssignment->getKey())
        ->and($visibleIds)->not->toContain($nestedSupervisorAssignment->getKey());

    actingAs($manager);

    expect(KpiAssignmentResource::canView($supervisorAssignment))->toBeTrue()
        ->and(KpiAssignmentResource::getEloquentQuery()->pluck('kpi_assignments.id')->all())
        ->toContain($supervisorAssignment->getKey());

    actingAs($directEmployee);

    expect(KpiAssignmentResource::canViewAny())->toBeTrue()
        ->and(KpiAssignmentResource::canView($employeeAssignment))->toBeTrue()
        ->and(KpiAssignmentResource::canView($otherEmployeeAssignment))->toBeFalse();
});

it('restricts supervisor assessment scope to direct employees only and protects locked records', function () {
    $hrd = makeSupervisorSecurityUser(SystemRole::HRD->value);
    $manager = makeSupervisorSecurityUser(SystemRole::MANAGER->value);
    $supervisor = makeSupervisorSecurityUser(SystemRole::SUPERVISOR->value, ['supervisor_id' => $manager->getKey()]);
    $otherSupervisor = makeSupervisorSecurityUser(SystemRole::SUPERVISOR->value);
    $subordinateSupervisor = makeSupervisorSecurityUser(SystemRole::SUPERVISOR->value, ['supervisor_id' => $supervisor->getKey()]);
    $unit = makeSupervisorSecurityUnit('Assessment');

    $directEmployee = makeSupervisorSecuritySubject('Scoped Employee', SystemRole::EMPLOYEE->value, $supervisor, $unit);
    $unrelatedEmployee = makeSupervisorSecuritySubject('Unrelated Employee', SystemRole::EMPLOYEE->value, $otherSupervisor, $unit);
    $directAssignment = assignSupervisorSecurityKpi($directEmployee, $hrd);
    $unrelatedAssignment = assignSupervisorSecurityKpi($unrelatedEmployee, $hrd);
    $subordinateSupervisorAssignment = assignSupervisorSecurityKpi($subordinateSupervisor, $hrd);

    $draft = app(CreateKpiAssessmentAction::class)->execute($directAssignment, $supervisor);

    app(UpdateKpiAssessmentAction::class)->execute($draft, ['notes' => 'Supervisor owns this draft'], $supervisor);
    $submitted = scoreAndSubmitSupervisorSecurityAssessment($draft->fresh('items'), $supervisor);
    actingAs($supervisor);

    expect($submitted->status)->toBe(KpiAssessmentStatus::SUBMITTED)
        ->and(KpiAssessmentResource::getEloquentQuery()->pluck('kpi_assessments.id')->all())
        ->toContain($submitted->getKey());

    expect(fn () => app(CreateKpiAssessmentAction::class)->execute($unrelatedAssignment, $supervisor))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => app(CreateKpiAssessmentAction::class)->execute($subordinateSupervisorAssignment, $supervisor))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => app(CreateKpiAssessmentAction::class)->execute(assignSupervisorSecurityKpi($supervisor, $hrd), $supervisor))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => assignSupervisorSecurityKpi($manager, $hrd))
        ->toThrow(ValidationException::class)
        ->and(fn () => assignSupervisorSecurityKpi(makeSupervisorSecurityUser(SystemRole::HRD->value), $hrd))
        ->toThrow(ValidationException::class)
        ->and(fn () => assignSupervisorSecurityKpi(makeSupervisorSecurityUser(SystemRole::APPROVER->value), $hrd))
        ->toThrow(ValidationException::class);

    $locked = $submitted->fresh();
    $locked->forceFill([
        'status' => KpiAssessmentStatus::LOCKED,
        'locked_at' => now(),
    ])->saveQuietly();

    expect(fn () => app(UpdateKpiAssessmentAction::class)->execute($locked->fresh(), ['notes' => 'Locked'], $supervisor))
        ->toThrow(AuthorizationException::class);
});

it('allows manager assessment flow for direct supervisors only', function () {
    $hrd = makeSupervisorSecurityUser(SystemRole::HRD->value);
    $manager = makeSupervisorSecurityUser(SystemRole::MANAGER->value);
    $otherManager = makeSupervisorSecurityUser(SystemRole::MANAGER->value);
    $directSupervisor = makeSupervisorSecurityUser(SystemRole::SUPERVISOR->value, ['supervisor_id' => $manager->getKey()]);
    $unrelatedSupervisor = makeSupervisorSecurityUser(SystemRole::SUPERVISOR->value, ['supervisor_id' => $otherManager->getKey()]);

    $directAssessment = app(CreateKpiAssessmentAction::class)->execute(assignSupervisorSecurityKpi($directSupervisor, $hrd), $manager);
    $submitted = scoreAndSubmitSupervisorSecurityAssessment($directAssessment->fresh('items'), $manager);

    expect($submitted->status)->toBe(KpiAssessmentStatus::SUBMITTED)
        ->and($submitted->assessor_id)->toBe($manager->getKey());

    expect(fn () => app(CreateKpiAssessmentAction::class)->execute(assignSupervisorSecurityKpi($unrelatedSupervisor, $hrd), $manager))
        ->toThrow(AuthorizationException::class);
});

it('preserves final workflow authority and keeps supervisor out of review approve reject and lock actions', function () {
    $assigner = makeSupervisorSecurityUser(SystemRole::HRD->value);
    $manager = makeSupervisorSecurityUser(SystemRole::MANAGER->value);
    $supervisor = makeSupervisorSecurityUser(SystemRole::SUPERVISOR->value, ['supervisor_id' => $manager->getKey()]);
    $employee = makeSupervisorSecuritySubject('Workflow Employee', SystemRole::EMPLOYEE->value, $supervisor, makeSupervisorSecurityUnit('Workflow'));
    $hrd = makeSupervisorSecurityUser(SystemRole::HRD->value);
    $approver = makeSupervisorSecurityUser(SystemRole::APPROVER->value);

    $submitted = scoreAndSubmitSupervisorSecurityAssessment(
        app(CreateKpiAssessmentAction::class)->execute(assignSupervisorSecurityKpi($employee, $assigner), $supervisor),
        $supervisor,
    );

    expect(fn () => app(ReviewKpiAssessmentAction::class)->execute($submitted->fresh(), [], $supervisor))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => app(RejectKpiAssessmentAction::class)->execute($submitted->fresh(), ['notes' => 'Nope'], $supervisor))
        ->toThrow(AuthorizationException::class);

    $reviewed = app(ReviewKpiAssessmentAction::class)->execute($submitted->fresh(), ['notes' => 'HRD review'], $hrd);

    expect($reviewed->status)->toBe(KpiAssessmentStatus::REVIEWED)
        ->and(fn () => app(ApproveKpiAssessmentAction::class)->execute($reviewed->fresh(), [], $supervisor))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => app(LockKpiAssessmentAction::class)->execute($reviewed->fresh(), [], $supervisor))
        ->toThrow(AuthorizationException::class);

    $approved = app(ApproveKpiAssessmentAction::class)->execute($reviewed->fresh(), ['notes' => 'Approver approval'], $approver);
    $locked = app(LockKpiAssessmentAction::class)->execute($approved->fresh(), ['notes' => 'Approver lock'], $approver);

    expect($approved->status)->toBe(KpiAssessmentStatus::APPROVED)
        ->and($locked->status)->toBe(KpiAssessmentStatus::LOCKED);
});

it('blocks supervisor from reports exports and private file exposure', function () {
    $supervisor = makeSupervisorSecurityUser(SystemRole::SUPERVISOR->value);
    $owner = makeSupervisorSecurityUser(SystemRole::MANAGER->value);

    $export = KpiReportExport::factory()->completed()->create([
        'requested_by' => $owner->getKey(),
        'disk' => 'local',
        'file_path' => 'exports/private-download.xlsx',
        'file_name' => 'private-download.xlsx',
    ]);

    Storage::disk('local')->put('exports/private-download.xlsx', 'secret spreadsheet');

    actingAs($supervisor);

    expect(KpiAssessmentReportResource::canViewAny())->toBeFalse()
        ->and(KpiReportExportResource::canViewAny())->toBeFalse();

    get('/admin/kpi-assessment-reports')
        ->assertForbidden()
        ->assertDontSee('storage/')
        ->assertDontSee('private/');

    get('/admin/kpi-report-exports')
        ->assertForbidden()
        ->assertDontSee('storage/')
        ->assertDontSee('private/');

    get(route('kpi-report-exports.download', $export))->assertForbidden();
});

it('lets a supervisor use self service for their own KPI but not another users detail', function () {
    $hrd = makeSupervisorSecurityUser(SystemRole::HRD->value);
    $manager = makeSupervisorSecurityUser(SystemRole::MANAGER->value);
    $supervisor = makeSupervisorSecurityUser(SystemRole::SUPERVISOR->value, ['supervisor_id' => $manager->getKey()]);
    $otherSupervisor = makeSupervisorSecurityUser(SystemRole::SUPERVISOR->value, ['supervisor_id' => $manager->getKey()]);

    $assessment = scoreAndSubmitSupervisorSecurityAssessment(
        app(CreateKpiAssessmentAction::class)->execute(assignSupervisorSecurityKpi($supervisor, $hrd), $manager),
        $manager,
    );

    actingAs($supervisor);

    get('/my/kpi-dashboard')
        ->assertOk()
        ->assertSee($assessment->assignment->template->name)
        ->assertDontSee('storage/app/private')
        ->assertDontSee('private/');

    get(route('my.kpi-assessments.show', ['kpiAssessment' => $assessment]))
        ->assertOk()
        ->assertDontSee('storage/app/private')
        ->assertDontSee('private/');

    actingAs($otherSupervisor);
    get(route('my.kpi-assessments.show', ['kpiAssessment' => $assessment]))->assertForbidden();
});
