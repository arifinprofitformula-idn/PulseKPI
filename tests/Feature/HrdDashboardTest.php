<?php

use App\Enums\KpiAssessmentStatus;
use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Models\KpiAssessment;
use App\Models\KpiAssignment;
use App\Models\KpiPeriod;
use App\Models\KpiReportExport;
use App\Models\KpiTemplate;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makeHrdDashboardUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function makeHrdDashboardAssessment(
    User $employee,
    User $assessor,
    KpiAssessmentStatus $status = KpiAssessmentStatus::SUBMITTED,
    array $attributes = [],
): KpiAssessment {
    $period = KpiPeriod::factory()->monthly()->create([
        'name' => $attributes['period_name'] ?? 'Januari 2026',
        'year' => 2026,
        'month' => 1,
    ]);

    $template = KpiTemplate::factory()->published()->create([
        'name' => $attributes['template_name'] ?? 'Template KPI HRD',
        'year' => 2026,
    ]);

    $assignment = KpiAssignment::factory()->create([
        'kpi_period_id' => $period->getKey(),
        'kpi_template_id' => $template->getKey(),
        'employee_id' => $employee->getKey(),
        'assigned_by' => $assessor->getKey(),
    ]);

    $assessment = KpiAssessment::factory()->create([
        'kpi_assignment_id' => $assignment->getKey(),
        'employee_id' => $employee->getKey(),
        'assessor_id' => $assessor->getKey(),
        'notes' => 'Assessment dashboard test',
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
            'submitted_at' => $attributes['submitted_at'] ?? now(),
            'reviewed_at' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'locked_at' => null,
        ],
        KpiAssessmentStatus::REVIEWED => [
            'submitted_at' => now()->subMinutes(2),
            'reviewed_at' => now(),
            'approved_at' => null,
            'rejected_at' => null,
            'locked_at' => null,
        ],
        KpiAssessmentStatus::APPROVED => [
            'submitted_at' => now()->subMinutes(3),
            'reviewed_at' => now()->subMinutes(2),
            'approved_at' => now(),
            'rejected_at' => null,
            'locked_at' => null,
        ],
        KpiAssessmentStatus::REJECTED => [
            'submitted_at' => now()->subMinutes(3),
            'reviewed_at' => null,
            'approved_at' => null,
            'rejected_at' => now(),
            'locked_at' => null,
        ],
        KpiAssessmentStatus::LOCKED => [
            'submitted_at' => now()->subMinutes(4),
            'reviewed_at' => now()->subMinutes(3),
            'approved_at' => now()->subMinutes(2),
            'rejected_at' => null,
            'locked_at' => now(),
        ],
    };

    $assessment->forceFill(array_merge([
        'status' => $status,
        'final_score' => $attributes['final_score'] ?? '88.00',
        'grade' => $attributes['grade'] ?? 'Good',
    ], $timestamps))->saveQuietly();

    return $assessment->fresh(['employee', 'assessor', 'assignment.period', 'assignment.template']);
}

it('allows hrd to access the hrd dashboard', function () {
    $hrd = makeHrdDashboardUser(SystemRole::HRD->value);

    actingAs($hrd);

    get('/admin/hrd-dashboard')
        ->assertOk()
        ->assertSee('Dashboard HRD');
});

it('allows super admin to access the hrd dashboard', function () {
    $user = makeHrdDashboardUser(SystemRole::SUPER_ADMIN->value);

    actingAs($user);

    get('/admin/hrd-dashboard')->assertOk();
});

it('forbids employees from accessing the hrd dashboard', function () {
    $user = makeHrdDashboardUser(SystemRole::EMPLOYEE->value);

    actingAs($user);

    get('/admin/hrd-dashboard')->assertForbidden();
});

it('forbids users without report permission from accessing the hrd dashboard', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(SystemPermission::ACCESS_ADMIN_PANEL->value);

    actingAs($user);

    get('/admin/hrd-dashboard')->assertForbidden();
});

it('allows managers with the existing report permission to access the hrd dashboard', function () {
    $user = makeHrdDashboardUser(SystemRole::MANAGER->value);

    actingAs($user);

    get('/admin/hrd-dashboard')->assertOk();
});

it('renders the dashboard without exposing private export paths', function () {
    $hrd = makeHrdDashboardUser(SystemRole::HRD->value);
    $manager = makeHrdDashboardUser(SystemRole::MANAGER->value);
    $employee = User::factory()->create([
        'name' => 'Dian Karyawan',
        'supervisor_id' => $manager->getKey(),
    ]);
    $assessment = makeHrdDashboardAssessment($employee, $manager);

    KpiReportExport::factory()->completed()->create([
        'requested_by' => $hrd->getKey(),
        'kpi_assessment_id' => $assessment->getKey(),
        'file_path' => 'private/exports/assessment-2026.xlsx',
        'file_name' => 'assessment-2026.xlsx',
    ]);

    actingAs($hrd);

    get('/admin/hrd-dashboard')
        ->assertOk()
        ->assertSee('Dian Karyawan')
        ->assertSee('assessment-2026.xlsx')
        ->assertDontSee('private/exports/assessment-2026.xlsx')
        ->assertSee('Pending review')
        ->assertSee('Export terbaru');
});

it('limits pending review records on the dashboard', function () {
    $hrd = makeHrdDashboardUser(SystemRole::HRD->value);
    $manager = makeHrdDashboardUser(SystemRole::MANAGER->value);

    foreach (range(1, 6) as $index) {
        $employee = User::factory()->create([
            'name' => "Review Employee {$index}",
            'supervisor_id' => $manager->getKey(),
        ]);

        makeHrdDashboardAssessment($employee, $manager, KpiAssessmentStatus::SUBMITTED, [
            'submitted_at' => now()->subMinutes($index),
            'template_name' => "Template {$index}",
        ]);
    }

    actingAs($hrd);

    get('/admin/hrd-dashboard')
        ->assertOk()
        ->assertSee('Review Employee 1')
        ->assertSee('Review Employee 5')
        ->assertDontSee('Review Employee 6');
});

it('renders human friendly empty states when no dashboard data exists', function () {
    $hrd = makeHrdDashboardUser(SystemRole::HRD->value);

    actingAs($hrd);

    get('/admin/hrd-dashboard')
        ->assertOk()
        ->assertSee('Belum ada assessment yang menunggu review.')
        ->assertSee('Belum ada export terbaru.')
        ->assertSee('Belum ada aktivitas terbaru untuk ditampilkan.')
        ->assertSee('Data performa divisi belum tersedia untuk cakupan ini.');
});
