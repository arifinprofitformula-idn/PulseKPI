<?php

use App\Enums\KpiAssessmentStatus;
use App\Enums\KpiAssignmentStatus;
use App\Enums\SystemRole;
use App\Models\Department;
use App\Models\Division;
use App\Models\KpiAssessment;
use App\Models\KpiAssignment;
use App\Models\KpiPeriod;
use App\Models\KpiTemplate;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makeSupervisorDashboardUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

/**
 * @return array{division: Division, department: Department, position: Position}
 */
function makeSupervisorDashboardUnit(string $name): array
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

function makeSupervisedEmployee(string $name, User $supervisor, array $unit): User
{
    $employee = User::factory()->create([
        'name' => $name,
        'division_id' => $unit['division']->getKey(),
        'department_id' => $unit['department']->getKey(),
        'position_id' => $unit['position']->getKey(),
        'supervisor_id' => $supervisor->getKey(),
    ]);
    $employee->assignRole(SystemRole::EMPLOYEE->value);

    return $employee;
}

function makeSupervisorDashboardPeriod(string $name, int $month): KpiPeriod
{
    return KpiPeriod::factory()->monthly()->create([
        'name' => $name,
        'year' => 2026,
        'month' => $month,
        'is_active' => true,
    ]);
}

function makeSupervisorDashboardTemplate(string $name): KpiTemplate
{
    return KpiTemplate::factory()->published()->create([
        'name' => $name,
        'year' => 2026,
    ]);
}

function createSupervisorDashboardAssessment(
    User $employee,
    User $supervisor,
    KpiAssessmentStatus $status,
    KpiPeriod $period,
    KpiTemplate $template,
    array $attributes = [],
): KpiAssessment {
    $assignment = KpiAssignment::factory()->create([
        'kpi_period_id' => $period->getKey(),
        'kpi_template_id' => $template->getKey(),
        'employee_id' => $employee->getKey(),
        'assigned_by' => $supervisor->getKey(),
        'status' => KpiAssignmentStatus::ASSIGNED->value,
        'assigned_at' => now()->subDays(5),
    ]);

    $assessment = KpiAssessment::factory()->create([
        'kpi_assignment_id' => $assignment->getKey(),
        'employee_id' => $employee->getKey(),
        'assessor_id' => $supervisor->getKey(),
        'notes' => 'Supervisor dashboard fixture',
    ]);

    $timestamps = match ($status) {
        KpiAssessmentStatus::DRAFT => ['submitted_at' => null, 'reviewed_at' => null, 'approved_at' => null, 'rejected_at' => null, 'locked_at' => null],
        KpiAssessmentStatus::SUBMITTED => ['submitted_at' => now()->subDays(2), 'reviewed_at' => null, 'approved_at' => null, 'rejected_at' => null, 'locked_at' => null],
        KpiAssessmentStatus::REJECTED => ['submitted_at' => now()->subDays(4), 'reviewed_at' => null, 'approved_at' => null, 'rejected_at' => now()->subDay(), 'locked_at' => null],
        KpiAssessmentStatus::APPROVED => ['submitted_at' => now()->subDays(4), 'reviewed_at' => now()->subDays(3), 'approved_at' => now()->subDays(2), 'rejected_at' => null, 'locked_at' => null],
        KpiAssessmentStatus::LOCKED => ['submitted_at' => now()->subDays(4), 'reviewed_at' => now()->subDays(3), 'approved_at' => now()->subDays(2), 'rejected_at' => null, 'locked_at' => now()->subDay()],
        KpiAssessmentStatus::REVIEWED => ['submitted_at' => now()->subDays(4), 'reviewed_at' => now()->subDays(3), 'approved_at' => null, 'rejected_at' => null, 'locked_at' => null],
    };

    $assessment->forceFill(array_merge([
        'status' => $status,
        'final_score' => $attributes['final_score'] ?? '82.40',
        'grade' => $attributes['grade'] ?? 'Good',
    ], $timestamps))->saveQuietly();

    return $assessment->fresh(['employee', 'assignment.period', 'assignment.template', 'assessor']);
}

it('Supervisor can access Supervisor dashboard and admin root redirects there', function () {
    $supervisor = makeSupervisorDashboardUser(SystemRole::SUPERVISOR->value);

    actingAs($supervisor);

    get('/admin')->assertRedirect('/admin/supervisor-dashboard');
    get('/admin/supervisor-dashboard')
        ->assertOk()
        ->assertSee('Dashboard Supervisor')
        ->assertSee('Selamat datang, Supervisor')
        ->assertSee('Open Assessment Queue');
});

it('Supervisor dashboard shows only direct staff metrics and hides other supervisors staff', function () {
    $supervisor = makeSupervisorDashboardUser(SystemRole::SUPERVISOR->value);
    $otherSupervisor = makeSupervisorDashboardUser(SystemRole::SUPERVISOR->value);
    $unit = makeSupervisorDashboardUnit('Sales');
    $otherUnit = makeSupervisorDashboardUnit('Ops');
    $employee = makeSupervisedEmployee('Visible Staff', $supervisor, $unit);
    $hiddenEmployee = makeSupervisedEmployee('Hidden Staff', $otherSupervisor, $otherUnit);
    $period = makeSupervisorDashboardPeriod('January 2026', 1);
    $template = makeSupervisorDashboardTemplate('Supervisor Dashboard Template');

    createSupervisorDashboardAssessment($employee, $supervisor, KpiAssessmentStatus::REJECTED, $period, $template, [
        'final_score' => '82.40',
    ]);
    createSupervisorDashboardAssessment($hiddenEmployee, $otherSupervisor, KpiAssessmentStatus::DRAFT, $period, $template, [
        'final_score' => '99.00',
    ]);

    actingAs($supervisor);

    get('/admin/supervisor-dashboard')
        ->assertOk()
        ->assertSee('Visible Staff')
        ->assertSee('82.40')
        ->assertDontSee('Hidden Staff')
        ->assertDontSee('99.00');
});

it('Supervisor dashboard renders empty state and hides unrelated navigation', function () {
    $supervisor = makeSupervisorDashboardUser(SystemRole::SUPERVISOR->value);

    actingAs($supervisor);

    get('/admin/supervisor-dashboard')
        ->assertOk()
        ->assertSee('No team members in your dashboard scope yet.')
        ->assertSee('No assignments are waiting on you right now.')
        ->assertSee('Team KPI')
        ->assertSee('Assessment Queue')
        ->assertDontSee('Dashboard HRD')
        ->assertDontSee('Manager Dashboard')
        ->assertDontSee('Approver Dashboard')
        ->assertDontSee('Exports')
        ->assertDontSee('Master Data');
});

it('Supervisor dashboard blocks Employee Manager HRD and Approver and hides private paths', function () {
    $supervisor = makeSupervisorDashboardUser(SystemRole::SUPERVISOR->value);
    $employee = makeSupervisorDashboardUser(SystemRole::EMPLOYEE->value);
    $manager = makeSupervisorDashboardUser(SystemRole::MANAGER->value);
    $hrd = makeSupervisorDashboardUser(SystemRole::HRD->value);
    $approver = makeSupervisorDashboardUser(SystemRole::APPROVER->value);

    actingAs($supervisor);
    get('/admin/supervisor-dashboard')
        ->assertOk()
        ->assertDontSee('private/')
        ->assertDontSee('storage/app/private');

    actingAs($employee);
    get('/admin/supervisor-dashboard')->assertForbidden();

    actingAs($manager);
    get('/admin/supervisor-dashboard')->assertForbidden();

    actingAs($hrd);
    get('/admin/supervisor-dashboard')->assertForbidden();

    actingAs($approver);
    get('/admin/supervisor-dashboard')->assertForbidden();
});

it('Supervisor cannot access HRD dashboard', function () {
    $supervisor = makeSupervisorDashboardUser(SystemRole::SUPERVISOR->value);

    actingAs($supervisor);

    get('/admin/hrd-dashboard')->assertForbidden();
});
