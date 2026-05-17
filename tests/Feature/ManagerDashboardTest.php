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

function makeManagerDashboardUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

/**
 * @return array{division: Division, department: Department, position: Position}
 */
function makeManagerDashboardUnit(string $name): array
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

function makeManagedEmployee(string $name, User $manager, array $unit): User
{
    $employee = User::factory()->create([
        'name' => $name,
        'division_id' => $unit['division']->getKey(),
        'department_id' => $unit['department']->getKey(),
        'position_id' => $unit['position']->getKey(),
        'supervisor_id' => $manager->getKey(),
    ]);
    $employee->assignRole(SystemRole::EMPLOYEE->value);

    return $employee;
}

function makeManagerPeriod(string $name, int $month): KpiPeriod
{
    return KpiPeriod::factory()->monthly()->create([
        'name' => $name,
        'year' => 2026,
        'month' => $month,
        'is_active' => true,
    ]);
}

function makeManagerTemplate(string $name): KpiTemplate
{
    return KpiTemplate::factory()->published()->create([
        'name' => $name,
        'year' => 2026,
    ]);
}

function createManagerDashboardAssessment(
    User $employee,
    User $manager,
    KpiAssessmentStatus $status,
    KpiPeriod $period,
    KpiTemplate $template,
    array $attributes = [],
): KpiAssessment {
    $assignment = KpiAssignment::factory()->create([
        'kpi_period_id' => $period->getKey(),
        'kpi_template_id' => $template->getKey(),
        'employee_id' => $employee->getKey(),
        'assigned_by' => $manager->getKey(),
        'status' => KpiAssignmentStatus::ASSIGNED->value,
        'assigned_at' => now()->subDays(5),
    ]);

    $assessment = KpiAssessment::factory()->create([
        'kpi_assignment_id' => $assignment->getKey(),
        'employee_id' => $employee->getKey(),
        'assessor_id' => $manager->getKey(),
        'notes' => 'Manager dashboard fixture',
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
        'final_score' => $attributes['final_score'] ?? '87.50',
        'grade' => $attributes['grade'] ?? 'Good',
    ], $timestamps))->saveQuietly();

    return $assessment->fresh(['employee', 'assignment.period', 'assignment.template', 'assessor']);
}

it('Manager dashboard is accessible and admin root redirects there', function () {
    $manager = makeManagerDashboardUser(SystemRole::MANAGER->value);

    actingAs($manager);

    get('/admin')->assertRedirect('/admin/manager-dashboard');
    get('/admin/manager-dashboard')
        ->assertOk()
        ->assertSee('PulseKPI Manager Workspace')
        ->assertSee('Open Assessment Queue');
});

it('Manager dashboard shows only direct subordinate data', function () {
    $manager = makeManagerDashboardUser(SystemRole::MANAGER->value);
    $otherManager = makeManagerDashboardUser(SystemRole::MANAGER->value);
    $unit = makeManagerDashboardUnit('Sales');
    $otherUnit = makeManagerDashboardUnit('Ops');
    $employee = makeManagedEmployee('Visible Team Member', $manager, $unit);
    $hiddenEmployee = makeManagedEmployee('Hidden Team Member', $otherManager, $otherUnit);
    $period = makeManagerPeriod('January 2026', 1);
    $template = makeManagerTemplate('Manager Dashboard Template');

    createManagerDashboardAssessment($employee, $manager, KpiAssessmentStatus::REJECTED, $period, $template, [
        'final_score' => '84.00',
    ]);
    createManagerDashboardAssessment($hiddenEmployee, $otherManager, KpiAssessmentStatus::DRAFT, $period, $template, [
        'final_score' => '99.00',
    ]);

    actingAs($manager);

    get('/admin/manager-dashboard')
        ->assertOk()
        ->assertSee('Visible Team Member')
        ->assertSee('84.00')
        ->assertDontSee('Hidden Team Member')
        ->assertDontSee('99.00');
});

it('Manager cannot access approver dashboard and HRD dashboard stays hidden from manager navigation', function () {
    $manager = makeManagerDashboardUser(SystemRole::MANAGER->value);

    actingAs($manager);

    get('/admin/approver-dashboard')->assertForbidden();
    get('/admin/manager-dashboard')
        ->assertOk()
        ->assertDontSee('Dashboard HRD');
});

it('Manager dashboard renders human friendly empty states and hides unrelated navigation', function () {
    $manager = makeManagerDashboardUser(SystemRole::MANAGER->value);

    actingAs($manager);

    get('/admin/manager-dashboard')
        ->assertOk()
        ->assertSee('No team members in your dashboard scope yet.')
        ->assertSee('No assignments are waiting on you right now.')
        ->assertSee('Team KPI')
        ->assertSee('Assessment Queue')
        ->assertSee('Assessment History')
        ->assertDontSee('Dashboard HRD')
        ->assertDontSee('Exports')
        ->assertDontSee('Master Data');
});

it('Manager dashboard does not expose private storage paths', function () {
    $manager = makeManagerDashboardUser(SystemRole::MANAGER->value);
    $unit = makeManagerDashboardUnit('Finance');
    $employee = makeManagedEmployee('Secure Employee', $manager, $unit);
    $period = makeManagerPeriod('February 2026', 2);
    $template = makeManagerTemplate('Secure Template');

    createManagerDashboardAssessment($employee, $manager, KpiAssessmentStatus::SUBMITTED, $period, $template);

    actingAs($manager);

    get('/admin/manager-dashboard')
        ->assertOk()
        ->assertDontSee('private/')
        ->assertDontSee('storage/app/private');
});
