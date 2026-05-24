<?php

use App\Enums\KpiApprovalAction;
use App\Enums\KpiAssessmentStatus;
use App\Enums\KpiAssignmentStatus;
use App\Enums\SystemRole;
use App\Models\KpiApproval;
use App\Models\KpiAssessment;
use App\Models\KpiAssignment;
use App\Models\KpiPeriod;
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

function makeApproverDashboardUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function createApproverAssessmentFixture(string $employeeName, User $manager, KpiAssessmentStatus $status): KpiAssessment
{
    $employee = User::factory()->create([
        'name' => $employeeName,
        'supervisor_id' => $manager->getKey(),
    ]);
    $employee->assignRole(SystemRole::EMPLOYEE->value);

    $period = KpiPeriod::factory()->monthly()->create([
        'name' => 'April 2026',
        'year' => 2026,
        'month' => 4,
        'is_active' => true,
    ]);
    $template = KpiTemplate::factory()->published()->create([
        'name' => 'Approver Dashboard Template',
        'year' => 2026,
    ]);
    $assignment = KpiAssignment::factory()->create([
        'kpi_period_id' => $period->getKey(),
        'kpi_template_id' => $template->getKey(),
        'employee_id' => $employee->getKey(),
        'assigned_by' => $manager->getKey(),
        'status' => KpiAssignmentStatus::ASSIGNED->value,
    ]);

    $assessment = KpiAssessment::factory()->create([
        'kpi_assignment_id' => $assignment->getKey(),
        'employee_id' => $employee->getKey(),
        'assessor_id' => $manager->getKey(),
        'notes' => 'Approver dashboard fixture',
    ]);

    $timestamps = match ($status) {
        KpiAssessmentStatus::DRAFT => ['submitted_at' => null, 'reviewed_at' => null, 'approved_at' => null, 'rejected_at' => null, 'locked_at' => null],
        KpiAssessmentStatus::SUBMITTED => ['submitted_at' => now()->subDays(2), 'reviewed_at' => null, 'approved_at' => null, 'rejected_at' => null, 'locked_at' => null],
        KpiAssessmentStatus::REVIEWED => ['submitted_at' => now()->subDays(3), 'reviewed_at' => now()->subDay(), 'approved_at' => null, 'rejected_at' => null, 'locked_at' => null],
        KpiAssessmentStatus::APPROVED => ['submitted_at' => now()->subDays(4), 'reviewed_at' => now()->subDays(3), 'approved_at' => now()->subDays(2), 'rejected_at' => null, 'locked_at' => null],
        KpiAssessmentStatus::REJECTED => ['submitted_at' => now()->subDays(4), 'reviewed_at' => null, 'approved_at' => null, 'rejected_at' => now()->subDay(), 'locked_at' => null],
        KpiAssessmentStatus::LOCKED => ['submitted_at' => now()->subDays(4), 'reviewed_at' => now()->subDays(3), 'approved_at' => now()->subDays(2), 'rejected_at' => null, 'locked_at' => now()->subDay()],
    };

    $assessment->forceFill(array_merge([
        'status' => $status,
        'final_score' => '91.25',
        'grade' => 'Excellent',
    ], $timestamps))->saveQuietly();

    return $assessment->fresh(['employee', 'assignment.period', 'assessor']);
}

it('Approver dashboard is accessible and admin root redirects there', function () {
    $approver = makeApproverDashboardUser(SystemRole::APPROVER->value);

    actingAs($approver);

    get('/admin')->assertRedirect('/admin/approver-dashboard');
    get('/admin/approver-dashboard')
        ->assertOk()
        ->assertSee('KPI Command Center')
        ->assertSee('Dashboard ini hanya menampilkan approval queue sesuai workflow Anda')
        ->assertSee('Open Approval Queue');
});

it('Approver dashboard shows only workflow-allowed records', function () {
    $approver = makeApproverDashboardUser(SystemRole::APPROVER->value);
    $manager = makeApproverDashboardUser(SystemRole::MANAGER->value);

    createApproverAssessmentFixture('Draft Employee', $manager, KpiAssessmentStatus::DRAFT);
    createApproverAssessmentFixture('Submitted Employee', $manager, KpiAssessmentStatus::SUBMITTED);
    createApproverAssessmentFixture('Reviewed Employee', $manager, KpiAssessmentStatus::REVIEWED);
    $approved = createApproverAssessmentFixture('Approved Employee', $manager, KpiAssessmentStatus::APPROVED);
    $rejected = createApproverAssessmentFixture('Rejected Employee', $manager, KpiAssessmentStatus::REJECTED);
    $locked = createApproverAssessmentFixture('Locked Employee', $manager, KpiAssessmentStatus::LOCKED);

    KpiApproval::factory()->create([
        'kpi_assessment_id' => $approved->getKey(),
        'actor_id' => $approver->getKey(),
        'action' => KpiApprovalAction::APPROVED->value,
        'from_status' => KpiAssessmentStatus::REVIEWED->value,
        'to_status' => KpiAssessmentStatus::APPROVED->value,
        'notes' => 'Approved cleanly.',
        'acted_at' => now()->subHour(),
    ]);
    KpiApproval::factory()->create([
        'kpi_assessment_id' => $locked->getKey(),
        'actor_id' => $approver->getKey(),
        'action' => KpiApprovalAction::LOCKED->value,
        'from_status' => KpiAssessmentStatus::APPROVED->value,
        'to_status' => KpiAssessmentStatus::LOCKED->value,
        'notes' => 'Locked for final archive.',
        'acted_at' => now()->subMinutes(30),
    ]);
    KpiApproval::factory()->create([
        'kpi_assessment_id' => $rejected->getKey(),
        'actor_id' => $approver->getKey(),
        'action' => KpiApprovalAction::REJECTED->value,
        'from_status' => KpiAssessmentStatus::REVIEWED->value,
        'to_status' => KpiAssessmentStatus::REJECTED->value,
        'notes' => 'Rejected after review.',
        'acted_at' => now()->subMinutes(10),
    ]);

    actingAs($approver);

    get('/admin/approver-dashboard')
        ->assertOk()
        ->assertSee('Reviewed Employee')
        ->assertSee('Approved Employee')
        ->assertSee('Locked Employee')
        ->assertDontSee('Draft Employee')
        ->assertDontSee('Submitted Employee')
        ->assertDontSee('Rejected Employee')
        ->assertSee('Hidden by workflow scope');
});

it('Approver dashboard renders empty states and hides unrelated navigation', function () {
    $approver = makeApproverDashboardUser(SystemRole::APPROVER->value);

    actingAs($approver);

    get('/admin/approver-dashboard')
        ->assertOk()
        ->assertSee('Tidak ada assessment yang membutuhkan tindakan saat ini.')
        ->assertSee('Semua pekerjaan sudah tertangani.')
        ->assertSee('Approval Queue')
        ->assertSee('Approval History')
        ->assertDontSee('Dashboard HRD')
        ->assertDontSee('Exports')
        ->assertDontSee('Master Data');
});

it('Approver dashboard does not expose private paths and blocks non-approvers', function () {
    $approver = makeApproverDashboardUser(SystemRole::APPROVER->value);
    $manager = makeApproverDashboardUser(SystemRole::MANAGER->value);
    $employee = makeApproverDashboardUser(SystemRole::EMPLOYEE->value);

    actingAs($approver);

    get('/admin/approver-dashboard')
        ->assertOk()
        ->assertDontSee('private/')
        ->assertDontSee('storage/app/private');

    actingAs($manager);
    get('/admin/approver-dashboard')->assertForbidden();

    actingAs($employee);
    get('/admin/approver-dashboard')->assertForbidden();
});
