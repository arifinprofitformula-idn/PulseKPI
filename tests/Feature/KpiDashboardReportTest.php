<?php

use App\Actions\Reports\BuildKpiAssessmentReportQuery;
use App\Enums\KpiAssessmentStatus;
use App\Enums\KpiAssignmentStatus;
use App\Enums\SystemRole;
use App\Filament\Resources\KpiAssessmentReports\Pages\ListKpiAssessmentReports;
use App\Models\Department;
use App\Models\Division;
use App\Models\KpiAssessment;
use App\Models\KpiAssignment;
use App\Models\KpiPeriod;
use App\Models\KpiTemplate;
use App\Models\Position;
use App\Models\User;
use App\Services\Dashboard\KpiDashboardService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Cache::flush();
});

function makeDashboardUser(string $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->assignRole($role);

    return $user;
}

/**
 * @return array{division: Division, department: Department, position: Position}
 */
function makeOrganizationUnit(string $name): array
{
    $division = Division::factory()->create([
        'name' => "{$name} Division",
    ]);
    $department = Department::factory()->create([
        'division_id' => $division->getKey(),
        'name' => "{$name} Department",
    ]);
    $position = Position::factory()->create([
        'department_id' => $department->getKey(),
        'name' => "{$name} Position",
    ]);

    return [
        'division' => $division,
        'department' => $department,
        'position' => $position,
    ];
}

function makeEmployeeInUnit(string $name, User $supervisor, array $unit): User
{
    return makeDashboardUser(SystemRole::EMPLOYEE->value, [
        'name' => $name,
        'division_id' => $unit['division']->getKey(),
        'department_id' => $unit['department']->getKey(),
        'position_id' => $unit['position']->getKey(),
        'supervisor_id' => $supervisor->getKey(),
    ]);
}

function makePeriod(string $name, int $year, ?int $month): KpiPeriod
{
    return KpiPeriod::factory()->create([
        'name' => $name,
        'year' => $year,
        'month' => $month,
        'type' => $month === null ? 'yearly' : 'monthly',
        'starts_at' => sprintf('%d-%02d-01', $year, $month ?? 1),
        'ends_at' => $month === null ? "{$year}-12-31" : now()->setDate($year, $month, 1)->endOfMonth()->toDateString(),
        'is_active' => true,
    ]);
}

function makeTemplate(string $name): KpiTemplate
{
    return KpiTemplate::factory()->published()->create([
        'name' => $name,
        'year' => 2026,
    ]);
}

function createDashboardAssessment(
    User $employee,
    User $assessor,
    KpiAssessmentStatus $status,
    KpiPeriod $period,
    KpiTemplate $template,
    array $attributes = [],
): KpiAssessment {
    $assignment = KpiAssignment::factory()->create([
        'kpi_period_id' => $period->getKey(),
        'kpi_template_id' => $template->getKey(),
        'employee_id' => $employee->getKey(),
        'assigned_by' => $assessor->getKey(),
        'status' => KpiAssignmentStatus::ASSIGNED->value,
        'assigned_at' => now()->subDays(7),
    ]);

    $timestamps = match ($status) {
        KpiAssessmentStatus::DRAFT => [
            'submitted_at' => null,
            'reviewed_at' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'locked_at' => null,
        ],
        KpiAssessmentStatus::SUBMITTED => [
            'submitted_at' => now()->subDays(4),
            'reviewed_at' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'locked_at' => null,
        ],
        KpiAssessmentStatus::REVIEWED => [
            'submitted_at' => now()->subDays(4),
            'reviewed_at' => now()->subDays(3),
            'approved_at' => null,
            'rejected_at' => null,
            'locked_at' => null,
        ],
        KpiAssessmentStatus::APPROVED => [
            'submitted_at' => now()->subDays(4),
            'reviewed_at' => now()->subDays(3),
            'approved_at' => now()->subDays(2),
            'rejected_at' => null,
            'locked_at' => null,
        ],
        KpiAssessmentStatus::REJECTED => [
            'submitted_at' => now()->subDays(4),
            'reviewed_at' => null,
            'approved_at' => null,
            'rejected_at' => now()->subDays(2),
            'locked_at' => null,
        ],
        KpiAssessmentStatus::LOCKED => [
            'submitted_at' => now()->subDays(4),
            'reviewed_at' => now()->subDays(3),
            'approved_at' => now()->subDays(2),
            'rejected_at' => null,
            'locked_at' => now()->subDay(),
        ],
    };

    $assessment = KpiAssessment::factory()->create(array_merge([
        'kpi_assignment_id' => $assignment->getKey(),
        'employee_id' => $employee->getKey(),
        'assessor_id' => $assessor->getKey(),
    ], $attributes));

    $assessment->forceFill(array_merge([
        'status' => $status,
        'kpi_score' => $attributes['kpi_score'] ?? '88.00',
        'attendance_score' => $attributes['attendance_score'] ?? '98.00',
        'attendance_deduction' => $attributes['attendance_deduction'] ?? '2.00',
        'final_score' => $attributes['final_score'] ?? '86.00',
        'grade' => $attributes['grade'] ?? 'Good',
    ], $timestamps))->saveQuietly();

    return $assessment->fresh([
        'assignment.period',
        'assignment.template',
        'employee.division',
        'employee.department',
        'employee.position',
        'assessor',
    ]);
}

function createDashboardAssignment(
    User $employee,
    User $assignedBy,
    KpiPeriod $period,
    KpiTemplate $template,
    array $attributes = [],
): KpiAssignment {
    return KpiAssignment::factory()->create(array_merge([
        'kpi_period_id' => $period->getKey(),
        'kpi_template_id' => $template->getKey(),
        'employee_id' => $employee->getKey(),
        'assigned_by' => $assignedBy->getKey(),
        'status' => KpiAssignmentStatus::ASSIGNED->value,
        'assigned_at' => now()->subDays(7),
    ], $attributes));
}

it('hrd sees organization level assessment counts', function () {
    $hrd = makeDashboardUser(SystemRole::HRD->value);
    $managerA = makeDashboardUser(SystemRole::MANAGER->value);
    $managerB = makeDashboardUser(SystemRole::MANAGER->value);
    $unitA = makeOrganizationUnit('Sales');
    $unitB = makeOrganizationUnit('Ops');
    $employeeA = makeEmployeeInUnit('Alice Employee', $managerA, $unitA);
    $employeeB = makeEmployeeInUnit('Bob Employee', $managerB, $unitB);
    $period = makePeriod('January 2026', 2026, 1);
    $template = makeTemplate('Main Template');

    createDashboardAssessment($employeeA, $managerA, KpiAssessmentStatus::SUBMITTED, $period, $template);
    createDashboardAssessment($employeeB, $managerB, KpiAssessmentStatus::APPROVED, $period, $template, [
        'final_score' => '92.00',
        'grade' => 'Excellent',
    ]);

    $stats = app(KpiDashboardService::class)->getStats($hrd);

    expect($stats['total_assessments'])->toBe(2)
        ->and($stats['submitted_assessments'])->toBe(1)
        ->and($stats['approved_assessments'])->toBe(1);
});

it('hrd total assignments include scoped assignments without assessments', function () {
    $hrd = makeDashboardUser(SystemRole::HRD->value);
    $manager = makeDashboardUser(SystemRole::MANAGER->value);
    $unit = makeOrganizationUnit('HRD Assignments');
    $employeeA = makeEmployeeInUnit('Assignment Employee A', $manager, $unit);
    $employeeB = makeEmployeeInUnit('Assignment Employee B', $manager, $unit);
    $period = makePeriod('HRD Assignment Period', 2026, 1);
    $template = makeTemplate('HRD Assignment Template');

    createDashboardAssessment($employeeA, $manager, KpiAssessmentStatus::SUBMITTED, $period, $template);
    createDashboardAssignment($employeeB, $manager, $period, $template);

    $stats = app(KpiDashboardService::class)->getStats($hrd);

    expect($stats['total_assignments'])->toBe(2)
        ->and($stats['total_assessments'])->toBe(1);
});

it('manager sees only direct subordinate assessment counts', function () {
    $manager = makeDashboardUser(SystemRole::MANAGER->value);
    $otherManager = makeDashboardUser(SystemRole::MANAGER->value);
    $unitA = makeOrganizationUnit('Finance');
    $unitB = makeOrganizationUnit('Support');
    $employee = makeEmployeeInUnit('Team Employee', $manager, $unitA);
    $otherEmployee = makeEmployeeInUnit('Other Employee', $otherManager, $unitB);
    $period = makePeriod('February 2026', 2026, 2);
    $template = makeTemplate('Manager Template');

    createDashboardAssessment($employee, $manager, KpiAssessmentStatus::DRAFT, $period, $template);
    createDashboardAssessment($otherEmployee, $otherManager, KpiAssessmentStatus::APPROVED, $period, $template);

    $stats = app(KpiDashboardService::class)->getStats($manager);

    expect($stats['total_assessments'])->toBe(1)
        ->and($stats['draft_assessments'])->toBe(1)
        ->and($stats['approved_assessments'])->toBe(0);
});

it('manager total assignments include only direct subordinate assignments without assessments', function () {
    $manager = makeDashboardUser(SystemRole::MANAGER->value);
    $otherManager = makeDashboardUser(SystemRole::MANAGER->value);
    $unitA = makeOrganizationUnit('Manager Assignment Scope');
    $unitB = makeOrganizationUnit('Other Assignment Scope');
    $employee = makeEmployeeInUnit('Manager Assignment Employee', $manager, $unitA);
    $otherEmployee = makeEmployeeInUnit('Other Assignment Employee', $otherManager, $unitB);
    $period = makePeriod('Manager Assignment Period', 2026, 2);
    $template = makeTemplate('Manager Assignment Template');

    createDashboardAssessment($employee, $manager, KpiAssessmentStatus::DRAFT, $period, $template);
    createDashboardAssignment(makeEmployeeInUnit('Manager Unassessed Employee', $manager, $unitA), $manager, $period, $template);
    createDashboardAssignment($otherEmployee, $otherManager, $period, $template);

    $stats = app(KpiDashboardService::class)->getStats($manager);

    expect($stats['total_assignments'])->toBe(2)
        ->and($stats['total_assessments'])->toBe(1);
});

it('employee total assignments include only own assignments', function () {
    $manager = makeDashboardUser(SystemRole::MANAGER->value);
    $unit = makeOrganizationUnit('Employee Assignment Scope');
    $employee = makeEmployeeInUnit('Own Assignment Employee', $manager, $unit);
    $otherEmployee = makeEmployeeInUnit('Other Assignment Employee', $manager, $unit);
    $period = makePeriod('Employee Assignment Period', 2026, 3);
    $template = makeTemplate('Employee Assignment Template');

    createDashboardAssessment($employee, $manager, KpiAssessmentStatus::REVIEWED, $period, $template);
    createDashboardAssignment($employee, $manager, makePeriod('Employee Extra Assignment', 2026, 4), $template);
    createDashboardAssignment($otherEmployee, $manager, $period, $template);

    $stats = app(KpiDashboardService::class)->getStats($employee);

    expect($stats['total_assignments'])->toBe(2)
        ->and($stats['total_assessments'])->toBe(1);
});

it('manager does not see unrelated employee assessment counts', function () {
    $manager = makeDashboardUser(SystemRole::MANAGER->value);
    $otherManager = makeDashboardUser(SystemRole::MANAGER->value);
    $unitA = makeOrganizationUnit('Product');
    $unitB = makeOrganizationUnit('Legal');
    $employee = makeEmployeeInUnit('Scoped Employee', $manager, $unitA);
    $otherEmployee = makeEmployeeInUnit('Hidden Employee', $otherManager, $unitB);
    $period = makePeriod('March 2026', 2026, 3);
    $template = makeTemplate('Scope Template');

    $visibleAssessment = createDashboardAssessment($employee, $manager, KpiAssessmentStatus::SUBMITTED, $period, $template);
    $hiddenAssessment = createDashboardAssessment($otherEmployee, $otherManager, KpiAssessmentStatus::SUBMITTED, $period, $template);

    actingAs($manager);

    Livewire::test(ListKpiAssessmentReports::class)
        ->assertCanSeeTableRecords([$visibleAssessment])
        ->assertCanNotSeeTableRecords([$hiddenAssessment]);
});

it('employee sees only own kpi dashboard', function () {
    $manager = makeDashboardUser(SystemRole::MANAGER->value);
    $otherManager = makeDashboardUser(SystemRole::MANAGER->value);
    $unitA = makeOrganizationUnit('Marketing');
    $unitB = makeOrganizationUnit('IT');
    $employee = makeEmployeeInUnit('Portal Employee', $manager, $unitA);
    $otherEmployee = makeEmployeeInUnit('Another Employee', $otherManager, $unitB);
    $period = makePeriod('April 2026', 2026, 4);
    $templateA = makeTemplate('Employee Template');
    $templateB = makeTemplate('Other Template');

    createDashboardAssessment($employee, $manager, KpiAssessmentStatus::REVIEWED, $period, $templateA, [
        'final_score' => '84.00',
        'grade' => 'Good',
    ]);
    createDashboardAssessment($otherEmployee, $otherManager, KpiAssessmentStatus::APPROVED, $period, $templateB, [
        'final_score' => '96.00',
        'grade' => 'Excellent',
    ]);

    actingAs($employee);

    get('/my/kpi-dashboard')
        ->assertOk()
        ->assertSee('Employee Template')
        ->assertSee('84.00')
        ->assertDontSee('Other Template')
        ->assertDontSee('96.00');
});

it('approver sees only reviewed approved and locked counts', function () {
    $approver = makeDashboardUser(SystemRole::APPROVER->value);
    $manager = makeDashboardUser(SystemRole::MANAGER->value);
    $unit = makeOrganizationUnit('Governance');
    $period = makePeriod('May 2026', 2026, 5);
    $template = makeTemplate('Approver Template');

    createDashboardAssessment(makeEmployeeInUnit('Draft Employee', $manager, $unit), $manager, KpiAssessmentStatus::DRAFT, $period, $template);
    createDashboardAssessment(makeEmployeeInUnit('Submitted Employee', $manager, $unit), $manager, KpiAssessmentStatus::SUBMITTED, $period, $template);
    createDashboardAssessment(makeEmployeeInUnit('Rejected Employee', $manager, $unit), $manager, KpiAssessmentStatus::REJECTED, $period, $template);
    createDashboardAssessment(makeEmployeeInUnit('Reviewed Employee', $manager, $unit), $manager, KpiAssessmentStatus::REVIEWED, $period, $template);
    createDashboardAssessment(makeEmployeeInUnit('Approved Employee', $manager, $unit), $manager, KpiAssessmentStatus::APPROVED, $period, $template);
    createDashboardAssessment(makeEmployeeInUnit('Locked Employee', $manager, $unit), $manager, KpiAssessmentStatus::LOCKED, $period, $template);

    $stats = app(KpiDashboardService::class)->getStats($approver);

    expect($stats['draft_assessments'])->toBe(0)
        ->and($stats['submitted_assessments'])->toBe(0)
        ->and($stats['rejected_assessments'])->toBe(0)
        ->and($stats['reviewed_assessments'])->toBe(1)
        ->and($stats['approved_assessments'])->toBe(1)
        ->and($stats['locked_assessments'])->toBe(1);
});

it('hrd can filter report data by period', function () {
    $hrd = makeDashboardUser(SystemRole::HRD->value);
    $manager = makeDashboardUser(SystemRole::MANAGER->value);
    $unit = makeOrganizationUnit('Filter');
    $employee = makeEmployeeInUnit('Filter Employee', $manager, $unit);
    $january = makePeriod('January 2026', 2026, 1);
    $february = makePeriod('February 2026', 2026, 2);
    $template = makeTemplate('Period Template');

    $januaryAssessment = createDashboardAssessment($employee, $manager, KpiAssessmentStatus::SUBMITTED, $january, $template);
    $februaryAssessment = createDashboardAssessment($employee, $manager, KpiAssessmentStatus::APPROVED, $february, $template);

    $results = app(BuildKpiAssessmentReportQuery::class)
        ->execute($hrd, ['period_id' => $january->getKey()])
        ->pluck('id')
        ->all();

    expect($results)->toContain($januaryAssessment->getKey())
        ->and($results)->not->toContain($februaryAssessment->getKey());
});

it('hrd can filter report data by division department and position', function () {
    $hrd = makeDashboardUser(SystemRole::HRD->value);
    $manager = makeDashboardUser(SystemRole::MANAGER->value);
    $sales = makeOrganizationUnit('Sales');
    $ops = makeOrganizationUnit('Operations');
    $salesEmployee = makeEmployeeInUnit('Sales Employee', $manager, $sales);
    $opsEmployee = makeEmployeeInUnit('Ops Employee', $manager, $ops);
    $period = makePeriod('June 2026', 2026, 6);
    $template = makeTemplate('Org Filter Template');

    $salesAssessment = createDashboardAssessment($salesEmployee, $manager, KpiAssessmentStatus::REVIEWED, $period, $template);
    $opsAssessment = createDashboardAssessment($opsEmployee, $manager, KpiAssessmentStatus::REVIEWED, $period, $template);

    $results = app(BuildKpiAssessmentReportQuery::class)
        ->execute($hrd, [
            'division_id' => $sales['division']->getKey(),
            'department_id' => $sales['department']->getKey(),
            'position_id' => $sales['position']->getKey(),
        ])
        ->pluck('id')
        ->all();

    expect($results)->toContain($salesAssessment->getKey())
        ->and($results)->not->toContain($opsAssessment->getKey());
});

it('hrd can filter report data by status and grade', function () {
    $hrd = makeDashboardUser(SystemRole::HRD->value);
    $manager = makeDashboardUser(SystemRole::MANAGER->value);
    $unit = makeOrganizationUnit('Quality');
    $employee = makeEmployeeInUnit('Quality Employee', $manager, $unit);
    $otherEmployee = makeEmployeeInUnit('Quality Employee 2', $manager, $unit);
    $period = makePeriod('July 2026', 2026, 7);
    $template = makeTemplate('Grade Template');

    $goodAssessment = createDashboardAssessment($employee, $manager, KpiAssessmentStatus::APPROVED, $period, $template, [
        'final_score' => '82.00',
        'grade' => 'Good',
    ]);
    $excellentAssessment = createDashboardAssessment($otherEmployee, $manager, KpiAssessmentStatus::APPROVED, $period, $template, [
        'final_score' => '94.00',
        'grade' => 'Excellent',
    ]);

    $results = app(BuildKpiAssessmentReportQuery::class)
        ->execute($hrd, [
            'status' => KpiAssessmentStatus::APPROVED->value,
            'grade' => 'Excellent',
        ])
        ->pluck('id')
        ->all();

    expect($results)->toContain($excellentAssessment->getKey())
        ->and($results)->not->toContain($goodAssessment->getKey());
});

it('manager report only returns direct subordinate rows', function () {
    $manager = makeDashboardUser(SystemRole::MANAGER->value);
    $otherManager = makeDashboardUser(SystemRole::MANAGER->value);
    $unitA = makeOrganizationUnit('People');
    $unitB = makeOrganizationUnit('Admin');
    $employee = makeEmployeeInUnit('Manager Row', $manager, $unitA);
    $otherEmployee = makeEmployeeInUnit('Hidden Row', $otherManager, $unitB);
    $period = makePeriod('August 2026', 2026, 8);
    $template = makeTemplate('Manager Report Template');

    $visibleAssessment = createDashboardAssessment($employee, $manager, KpiAssessmentStatus::SUBMITTED, $period, $template);
    $hiddenAssessment = createDashboardAssessment($otherEmployee, $otherManager, KpiAssessmentStatus::SUBMITTED, $period, $template);

    $results = app(BuildKpiAssessmentReportQuery::class)
        ->execute($manager)
        ->pluck('id')
        ->all();

    expect($results)->toContain($visibleAssessment->getKey())
        ->and($results)->not->toContain($hiddenAssessment->getKey());
});

it('employee report only returns own rows', function () {
    $manager = makeDashboardUser(SystemRole::MANAGER->value);
    $unit = makeOrganizationUnit('Employee Scope');
    $employee = makeEmployeeInUnit('Own Employee', $manager, $unit);
    $otherEmployee = makeEmployeeInUnit('Other Employee', $manager, $unit);
    $period = makePeriod('September 2026', 2026, 9);
    $template = makeTemplate('Employee Report Template');

    $ownAssessment = createDashboardAssessment($employee, $manager, KpiAssessmentStatus::REVIEWED, $period, $template);
    $otherAssessment = createDashboardAssessment($otherEmployee, $manager, KpiAssessmentStatus::REVIEWED, $period, $template);

    $results = app(BuildKpiAssessmentReportQuery::class)
        ->execute($employee)
        ->pluck('id')
        ->all();

    expect($results)->toContain($ownAssessment->getKey())
        ->and($results)->not->toContain($otherAssessment->getKey());
});

it('approver report only returns reviewed approved and locked rows', function () {
    $approver = makeDashboardUser(SystemRole::APPROVER->value);
    $manager = makeDashboardUser(SystemRole::MANAGER->value);
    $unit = makeOrganizationUnit('Approver Scope');
    $period = makePeriod('October 2026', 2026, 10);
    $template = makeTemplate('Approver Report Template');

    $reviewed = createDashboardAssessment(makeEmployeeInUnit('Reviewed Row', $manager, $unit), $manager, KpiAssessmentStatus::REVIEWED, $period, $template);
    $approved = createDashboardAssessment(makeEmployeeInUnit('Approved Row', $manager, $unit), $manager, KpiAssessmentStatus::APPROVED, $period, $template);
    $locked = createDashboardAssessment(makeEmployeeInUnit('Locked Row', $manager, $unit), $manager, KpiAssessmentStatus::LOCKED, $period, $template);
    $submitted = createDashboardAssessment(makeEmployeeInUnit('Submitted Row', $manager, $unit), $manager, KpiAssessmentStatus::SUBMITTED, $period, $template);

    $results = app(BuildKpiAssessmentReportQuery::class)
        ->execute($approver)
        ->pluck('id')
        ->all();

    expect($results)->toContain($reviewed->getKey(), $approved->getKey(), $locked->getKey())
        ->and($results)->not->toContain($submitted->getKey());
});

it('guests cannot access the report resource page', function () {
    get('/admin/kpi-assessment-reports')->assertRedirect('/admin/login');
});

it('employees cannot access the organization level report page', function () {
    $employee = makeDashboardUser(SystemRole::EMPLOYEE->value);

    actingAs($employee);

    get('/admin/kpi-assessment-reports')->assertForbidden();
});

it('manager cannot access unrelated assessment detail', function () {
    $manager = makeDashboardUser(SystemRole::MANAGER->value);
    $otherManager = makeDashboardUser(SystemRole::MANAGER->value);
    $unitA = makeOrganizationUnit('Visible');
    $unitB = makeOrganizationUnit('Hidden');
    $employee = makeEmployeeInUnit('Visible Detail', $manager, $unitA);
    $otherEmployee = makeEmployeeInUnit('Hidden Detail', $otherManager, $unitB);
    $period = makePeriod('November 2026', 2026, 11);
    $template = makeTemplate('Detail Template');

    createDashboardAssessment($employee, $manager, KpiAssessmentStatus::SUBMITTED, $period, $template);
    $hiddenAssessment = createDashboardAssessment($otherEmployee, $otherManager, KpiAssessmentStatus::SUBMITTED, $period, $template);

    actingAs($manager);

    get("/admin/kpi-assessments/{$hiddenAssessment->getKey()}/edit")
        ->assertStatus(404);
});

it('report table applies period filters in the filament page', function () {
    $hrd = makeDashboardUser(SystemRole::HRD->value);
    $manager = makeDashboardUser(SystemRole::MANAGER->value);
    $unit = makeOrganizationUnit('Filament');
    $employee = makeEmployeeInUnit('Filament Employee', $manager, $unit);
    $january = makePeriod('January 2026', 2026, 1);
    $february = makePeriod('February 2026', 2026, 2);
    $template = makeTemplate('Filament Template');

    $januaryAssessment = createDashboardAssessment($employee, $manager, KpiAssessmentStatus::APPROVED, $january, $template);
    $februaryAssessment = createDashboardAssessment($employee, $manager, KpiAssessmentStatus::APPROVED, $february, $template);

    actingAs($hrd);

    Livewire::test(ListKpiAssessmentReports::class)
        ->filterTable('report_filters', ['period_id' => $january->getKey()])
        ->assertCanSeeTableRecords([$januaryAssessment])
        ->assertCanNotSeeTableRecords([$februaryAssessment]);
});

it('report table uses pagination', function () {
    $hrd = makeDashboardUser(SystemRole::HRD->value);
    $manager = makeDashboardUser(SystemRole::MANAGER->value);
    $unit = makeOrganizationUnit('Paging');
    $period = makePeriod('December 2026', 2026, 12);
    $template = makeTemplate('Paging Template');

    foreach (range(1, 30) as $index) {
        $employee = makeEmployeeInUnit("Paging Employee {$index}", $manager, $unit);
        createDashboardAssessment($employee, $manager, KpiAssessmentStatus::APPROVED, $period, $template);
    }

    actingAs($hrd);

    Livewire::test(ListKpiAssessmentReports::class)
        ->assertSet('tableRecordsPerPage', 25)
        ->assertSee('Showing 1 to 25 of 30 results');
});

it('dashboard cache key includes user id and scope context', function () {
    $service = app(KpiDashboardService::class);
    $managerA = makeDashboardUser(SystemRole::MANAGER->value);
    $managerB = makeDashboardUser(SystemRole::MANAGER->value);

    $keyA = $service->makeCacheKey($managerA, ['period_id' => 1]);
    $keyB = $service->makeCacheKey($managerB, ['period_id' => 1]);
    $keyC = $service->makeCacheKey($managerA, ['period_id' => 2]);

    expect($keyA)->toContain((string) $managerA->getKey())
        ->and($keyA)->not->toBe($keyB)
        ->and($keyA)->not->toBe($keyC);
});

it('dashboard service caches scoped results safely', function () {
    $manager = makeDashboardUser(SystemRole::MANAGER->value);
    $unit = makeOrganizationUnit('Cache');
    $employee = makeEmployeeInUnit('Cache Employee', $manager, $unit);
    $period = makePeriod('Cache Period', 2026, 1);
    $template = makeTemplate('Cache Template');

    createDashboardAssessment($employee, $manager, KpiAssessmentStatus::SUBMITTED, $period, $template);

    $service = app(KpiDashboardService::class);
    $key = $service->makeCacheKey($manager);
    $stats = $service->getStats($manager);

    expect(Cache::has($key))->toBeTrue()
        ->and($stats['submitted_assessments'])->toBe(1);
});
