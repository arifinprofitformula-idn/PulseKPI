<?php

use App\Actions\Reports\BuildKpiAssessmentReportQuery;
use App\Actions\Reports\KpiAssessmentDetailPdfExportAction;
use App\Actions\Reports\RequestKpiAssessmentReportExportAction;
use App\Enums\KpiApprovalAction;
use App\Enums\KpiAssessmentStatus;
use App\Enums\KpiAssignmentStatus;
use App\Enums\KpiReportExportFormat;
use App\Enums\KpiReportExportStatus;
use App\Enums\KpiReportExportType;
use App\Enums\SystemRole;
use App\Exports\KpiAssessmentReportExcelExport;
use App\Jobs\ExportKpiAssessmentReportJob;
use App\Models\Department;
use App\Models\Division;
use App\Models\KpiApproval;
use App\Models\KpiAssessment;
use App\Models\KpiAssessmentItem;
use App\Models\KpiAssignment;
use App\Models\KpiPeriod;
use App\Models\KpiReportExport;
use App\Models\KpiTemplate;
use App\Models\Position;
use App\Models\User;
use App\Notifications\KpiReportExportCompletedNotification;
use App\Notifications\KpiReportExportFailedNotification;
use App\Services\Audit\ActivityLogService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('local');
});

function makeExportUser(string $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->assignRole($role);

    return $user;
}

/**
 * @return array{division: Division, department: Department, position: Position}
 */
function makeExportOrganizationUnit(string $name): array
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

function makeScopedEmployee(string $name, User $supervisor, array $unit): User
{
    return makeExportUser(SystemRole::EMPLOYEE->value, [
        'name' => $name,
        'division_id' => $unit['division']->getKey(),
        'department_id' => $unit['department']->getKey(),
        'position_id' => $unit['position']->getKey(),
        'supervisor_id' => $supervisor->getKey(),
    ]);
}

function makeExportPeriod(string $name, int $year, ?int $month): KpiPeriod
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

function makeExportTemplate(string $name): KpiTemplate
{
    return KpiTemplate::factory()->published()->create([
        'name' => $name,
        'year' => 2026,
    ]);
}

function createExportAssessment(
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
        'notes' => 'Scoped export note',
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

function addAssessmentPdfDetails(KpiAssessment $assessment, User $reviewActor, User $approveActor): KpiAssessment
{
    KpiAssessmentItem::factory()->create([
        'kpi_assessment_id' => $assessment->getKey(),
        'template_item_name' => 'Revenue Growth',
        'template_item_description' => 'Quarterly revenue target',
        'template_item_target_description' => 'Reach 120 units',
        'template_item_data_source' => 'Sales ledger',
        'template_item_weight' => '40.00',
        'template_item_is_required' => true,
        'actual_value' => '118.00',
        'score' => 92,
        'weighted_score' => '36.80',
        'evidence_note' => 'Backed by signed invoices',
        'evidence_file_path' => 'private/evidence/secret.pdf',
        'evidence_original_name' => 'secret.pdf',
    ]);

    KpiApproval::factory()->create([
        'kpi_assessment_id' => $assessment->getKey(),
        'actor_id' => $reviewActor->getKey(),
        'action' => KpiApprovalAction::REVIEWED,
        'from_status' => KpiAssessmentStatus::SUBMITTED->value,
        'to_status' => KpiAssessmentStatus::REVIEWED->value,
        'notes' => 'Reviewed by HRD',
        'acted_at' => now()->subDays(3),
    ]);

    KpiApproval::factory()->create([
        'kpi_assessment_id' => $assessment->getKey(),
        'actor_id' => $approveActor->getKey(),
        'action' => KpiApprovalAction::APPROVED,
        'from_status' => KpiAssessmentStatus::REVIEWED->value,
        'to_status' => KpiAssessmentStatus::APPROVED->value,
        'notes' => 'Approved by approver',
        'acted_at' => now()->subDays(2),
    ]);

    return $assessment->fresh([
        'assignment.period',
        'assignment.template',
        'employee.division',
        'employee.department',
        'employee.position',
        'assessor',
        'items',
        'attendanceAdjustment',
        'approvals.actor',
    ]);
}

it('hrd can request a queued assessment report export', function () {
    Queue::fake();

    $hrd = makeExportUser(SystemRole::HRD->value);

    $export = app(RequestKpiAssessmentReportExportAction::class)->execute($hrd, [
        'year' => 2026,
    ]);

    expect($export->requested_by)->toBe($hrd->getKey())
        ->and($export->status)->toBe(KpiReportExportStatus::PENDING);

    Queue::assertPushed(ExportKpiAssessmentReportJob::class, fn (ExportKpiAssessmentReportJob $job): bool => $job->exportId === $export->getKey());

    $this->assertDatabaseHas('activity_log', [
        'event' => 'kpi_report_export.requested',
        'subject_type' => KpiReportExport::class,
        'subject_id' => $export->getKey(),
        'causer_id' => $hrd->getKey(),
    ]);
});

it('manager excel export only includes direct subordinate assessments and respects filters', function () {
    $manager = makeExportUser(SystemRole::MANAGER->value);
    $otherManager = makeExportUser(SystemRole::MANAGER->value);
    $teamUnit = makeExportOrganizationUnit('Team Scope');
    $otherUnit = makeExportOrganizationUnit('Other Scope');
    $periodA = makeExportPeriod('January 2026', 2026, 1);
    $periodB = makeExportPeriod('February 2026', 2026, 2);
    $template = makeExportTemplate('Manager Export Template');

    $visibleEmployee = makeScopedEmployee('Visible Employee', $manager, $teamUnit);
    $secondVisibleEmployee = makeScopedEmployee('Second Visible Employee', $manager, $teamUnit);
    $hiddenEmployee = makeScopedEmployee('Hidden Employee', $otherManager, $otherUnit);

    createExportAssessment($visibleEmployee, $manager, KpiAssessmentStatus::APPROVED, $periodA, $template, [
        'grade' => 'Excellent',
        'final_score' => '94.00',
    ]);
    createExportAssessment($secondVisibleEmployee, $manager, KpiAssessmentStatus::APPROVED, $periodB, $template, [
        'grade' => 'Good',
        'final_score' => '85.00',
    ]);
    createExportAssessment($hiddenEmployee, $otherManager, KpiAssessmentStatus::APPROVED, $periodA, $template, [
        'grade' => 'Excellent',
        'final_score' => '95.00',
    ]);

    $export = KpiReportExport::factory()->create([
        'requested_by' => $manager->getKey(),
        'type' => KpiReportExportType::ASSESSMENT_REPORT,
        'format' => KpiReportExportFormat::XLSX,
        'status' => KpiReportExportStatus::PENDING,
        'filters' => [
            'period_id' => $periodA->getKey(),
            'grade' => 'Excellent',
        ],
        'disk' => 'local',
    ]);
    $exportedRows = [];

    Excel::shouldReceive('store')
        ->once()
        ->andReturnUsing(function (KpiAssessmentReportExcelExport $excelExport, string $filePath, string $disk) use (&$exportedRows): bool {
            $exportedRows = $excelExport
                ->query()
                ->get()
                ->map(fn (KpiAssessment $assessment): array => [
                    'employee' => $assessment->employee?->name ?? '',
                    'grade' => $assessment->grade ?? '',
                    'period' => $assessment->assignment?->period?->name ?? '',
                ])
                ->all();

            Storage::disk($disk)->put($filePath, 'fake xlsx bytes');

            return true;
        });

    auth()->logout();

    app(ExportKpiAssessmentReportJob::class, ['exportId' => $export->getKey()])->handle(
        app(ActivityLogService::class),
        app(BuildKpiAssessmentReportQuery::class)
    );

    $export->refresh();

    expect($export->status)->toBe(KpiReportExportStatus::COMPLETED)
        ->and($export->total_rows)->toBe(1);

    Storage::disk('local')->assertExists((string) $export->file_path);
    expect($exportedRows)->toHaveCount(1)
        ->and($exportedRows[0]['employee'])->toBe('Visible Employee')
        ->and($exportedRows[0]['grade'])->toBe('Excellent')
        ->and($exportedRows[0]['period'])->toBe('January 2026');

    $this->assertDatabaseHas('activity_log', [
        'event' => 'kpi_report_export.completed',
        'subject_type' => KpiReportExport::class,
        'subject_id' => $export->getKey(),
        'causer_id' => $manager->getKey(),
    ]);
    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $manager->getKey(),
        'type' => KpiReportExportCompletedNotification::class,
    ]);
});

it('employee cannot request organization level report export', function () {
    $employee = makeExportUser(SystemRole::EMPLOYEE->value);

    expect(fn () => app(RequestKpiAssessmentReportExportAction::class)->execute($employee))
        ->toThrow(AuthorizationException::class);
});

it('approver excel export only includes reviewed approved and locked assessments', function () {
    $approver = makeExportUser(SystemRole::APPROVER->value);
    $manager = makeExportUser(SystemRole::MANAGER->value);
    $unit = makeExportOrganizationUnit('Approver Export');
    $period = makeExportPeriod('March 2026', 2026, 3);
    $template = makeExportTemplate('Approver Export Template');

    createExportAssessment(makeScopedEmployee('Reviewed Employee', $manager, $unit), $manager, KpiAssessmentStatus::REVIEWED, $period, $template);
    createExportAssessment(makeScopedEmployee('Approved Employee', $manager, $unit), $manager, KpiAssessmentStatus::APPROVED, $period, $template);
    createExportAssessment(makeScopedEmployee('Locked Employee', $manager, $unit), $manager, KpiAssessmentStatus::LOCKED, $period, $template);
    createExportAssessment(makeScopedEmployee('Submitted Employee', $manager, $unit), $manager, KpiAssessmentStatus::SUBMITTED, $period, $template);
    createExportAssessment(makeScopedEmployee('Rejected Employee', $manager, $unit), $manager, KpiAssessmentStatus::REJECTED, $period, $template);

    $rows = (new KpiAssessmentReportExcelExport($approver))
        ->query()
        ->get()
        ->map(fn (KpiAssessment $assessment): string => $assessment->employee?->name ?? '')
        ->all();

    expect($rows)->toContain('Reviewed Employee', 'Approved Employee', 'Locked Employee')
        ->and($rows)->not->toContain('Submitted Employee')
        ->and($rows)->not->toContain('Rejected Employee');
});

it('failed excel export job marks the export as failed', function () {
    $manager = makeExportUser(SystemRole::MANAGER->value);

    $export = KpiReportExport::factory()->create([
        'requested_by' => $manager->getKey(),
        'disk' => 'local',
    ]);

    $failingAction = new class extends BuildKpiAssessmentReportQuery
    {
        public function execute(User $user, array $filters = []): Builder
        {
            throw new RuntimeException('Forced export failure for testing.');
        }
    };

    expect(function () use ($export, $failingAction): void {
        app(ExportKpiAssessmentReportJob::class, ['exportId' => $export->getKey()])->handle(
            app(ActivityLogService::class),
            $failingAction
        );
    })->toThrow(RuntimeException::class);

    $export->refresh();

    expect($export->status)->toBe(KpiReportExportStatus::FAILED)
        ->and($export->failed_at)->not->toBeNull()
        ->and($export->error_message)->toContain('Forced export failure');

    $this->assertDatabaseHas('activity_log', [
        'event' => 'kpi_report_export.failed',
        'subject_type' => KpiReportExport::class,
        'subject_id' => $export->getKey(),
        'causer_id' => $manager->getKey(),
    ]);
    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $manager->getKey(),
        'type' => KpiReportExportFailedNotification::class,
    ]);
});

it('hrd can export a visible assessment detail pdf', function () {
    $hrd = makeExportUser(SystemRole::HRD->value);
    $manager = makeExportUser(SystemRole::MANAGER->value);
    $approver = makeExportUser(SystemRole::APPROVER->value);
    $unit = makeExportOrganizationUnit('PDF HRD');
    $period = makeExportPeriod('May 2026', 2026, 5);
    $template = makeExportTemplate('PDF Detail Template');

    $assessment = addAssessmentPdfDetails(
        createExportAssessment(makeScopedEmployee('PDF Employee', $manager, $unit), $manager, KpiAssessmentStatus::APPROVED, $period, $template),
        $hrd,
        $approver
    );

    $export = app(KpiAssessmentDetailPdfExportAction::class)->execute($assessment, $hrd);

    expect($export->status)->toBe(KpiReportExportStatus::COMPLETED)
        ->and($export->type->value)->toBe('assessment_detail');

    Storage::disk('local')->assertExists((string) $export->file_path);

    $this->assertDatabaseHas('activity_log', [
        'event' => 'kpi_assessment.pdf_export_completed',
        'subject_type' => KpiAssessment::class,
        'subject_id' => $assessment->getKey(),
        'causer_id' => $hrd->getKey(),
    ]);
});

it('manager can export direct subordinate assessment detail pdf', function () {
    $manager = makeExportUser(SystemRole::MANAGER->value);
    $hrd = makeExportUser(SystemRole::HRD->value);
    $approver = makeExportUser(SystemRole::APPROVER->value);
    $unit = makeExportOrganizationUnit('PDF Manager');
    $period = makeExportPeriod('June 2026', 2026, 6);
    $template = makeExportTemplate('PDF Manager Template');

    $assessment = addAssessmentPdfDetails(
        createExportAssessment(makeScopedEmployee('Manager PDF Employee', $manager, $unit), $manager, KpiAssessmentStatus::REVIEWED, $period, $template),
        $hrd,
        $approver
    );

    $export = app(KpiAssessmentDetailPdfExportAction::class)->execute($assessment, $manager);

    expect($export->status)->toBe(KpiReportExportStatus::COMPLETED);
});

it('employee can export own assessment detail pdf but not another employee assessment', function () {
    $manager = makeExportUser(SystemRole::MANAGER->value);
    $hrd = makeExportUser(SystemRole::HRD->value);
    $approver = makeExportUser(SystemRole::APPROVER->value);
    $unit = makeExportOrganizationUnit('PDF Employee');
    $period = makeExportPeriod('July 2026', 2026, 7);
    $template = makeExportTemplate('PDF Employee Template');

    $employee = makeScopedEmployee('Self PDF Employee', $manager, $unit);
    $otherEmployee = makeScopedEmployee('Other PDF Employee', $manager, $unit);

    $ownAssessment = addAssessmentPdfDetails(
        createExportAssessment($employee, $manager, KpiAssessmentStatus::SUBMITTED, $period, $template),
        $hrd,
        $approver
    );
    $otherAssessment = addAssessmentPdfDetails(
        createExportAssessment($otherEmployee, $manager, KpiAssessmentStatus::SUBMITTED, $period, $template),
        $hrd,
        $approver
    );

    $ownExport = app(KpiAssessmentDetailPdfExportAction::class)->execute($ownAssessment, $employee);

    expect($ownExport->status)->toBe(KpiReportExportStatus::COMPLETED);

    expect(fn () => app(KpiAssessmentDetailPdfExportAction::class)->execute($otherAssessment, $employee))
        ->toThrow(AuthorizationException::class);
});

it('approver cannot export draft or submitted assessment detail pdf', function () {
    $approver = makeExportUser(SystemRole::APPROVER->value);
    $manager = makeExportUser(SystemRole::MANAGER->value);
    $unit = makeExportOrganizationUnit('PDF Approver');
    $period = makeExportPeriod('August 2026', 2026, 8);
    $template = makeExportTemplate('PDF Approver Template');

    $draftAssessment = createExportAssessment(makeScopedEmployee('Draft PDF Employee', $manager, $unit), $manager, KpiAssessmentStatus::DRAFT, $period, $template);
    $submittedAssessment = createExportAssessment(makeScopedEmployee('Submitted PDF Employee', $manager, $unit), $manager, KpiAssessmentStatus::SUBMITTED, $period, $template);

    expect(fn () => app(KpiAssessmentDetailPdfExportAction::class)->execute($draftAssessment, $approver))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(KpiAssessmentDetailPdfExportAction::class)->execute($submittedAssessment, $approver))
        ->toThrow(AuthorizationException::class);
});

it('assessment detail pdf view uses snapshot item fields and includes approval history without evidence file paths', function () {
    $manager = makeExportUser(SystemRole::MANAGER->value);
    $hrd = makeExportUser(SystemRole::HRD->value);
    $approver = makeExportUser(SystemRole::APPROVER->value);
    $unit = makeExportOrganizationUnit('PDF Render');
    $period = makeExportPeriod('September 2026', 2026, 9);
    $template = makeExportTemplate('PDF Render Template');

    $assessment = addAssessmentPdfDetails(
        createExportAssessment(makeScopedEmployee('Rendered PDF Employee', $manager, $unit), $manager, KpiAssessmentStatus::APPROVED, $period, $template),
        $hrd,
        $approver
    );

    $html = view('exports.kpi-assessment-detail', [
        'assessment' => $assessment,
    ])->render();

    expect($html)->toContain('Revenue Growth')
        ->and($html)->toContain('Quarterly revenue target')
        ->and($html)->toContain('Backed by signed invoices')
        ->and($html)->toContain('Reviewed by HRD')
        ->and($html)->toContain('Approved by approver')
        ->and($html)->not->toContain('private/evidence/secret.pdf');
});

it('owner can download a completed export and download is audited', function () {
    $manager = makeExportUser(SystemRole::MANAGER->value);

    $export = KpiReportExport::factory()->completed()->create([
        'requested_by' => $manager->getKey(),
        'disk' => 'local',
        'file_path' => 'exports/test-download.xlsx',
        'file_name' => 'test-download.xlsx',
    ]);

    Storage::disk('local')->put('exports/test-download.xlsx', 'spreadsheet bytes');

    actingAs($manager);

    get(route('kpi-report-exports.download', $export))
        ->assertOk()
        ->assertDownload('test-download.xlsx');

    $this->assertDatabaseHas('activity_log', [
        'event' => 'kpi_report_export.downloaded',
        'subject_type' => KpiReportExport::class,
        'subject_id' => $export->getKey(),
        'causer_id' => $manager->getKey(),
    ]);
});

it('unauthorized user cannot download another users export', function () {
    $owner = makeExportUser(SystemRole::MANAGER->value);
    $otherManager = makeExportUser(SystemRole::MANAGER->value);

    $export = KpiReportExport::factory()->completed()->create([
        'requested_by' => $owner->getKey(),
        'disk' => 'local',
        'file_path' => 'exports/private-download.xlsx',
        'file_name' => 'private-download.xlsx',
    ]);

    Storage::disk('local')->put('exports/private-download.xlsx', 'spreadsheet bytes');

    actingAs($otherManager);

    get(route('kpi-report-exports.download', $export))->assertForbidden();
});

it('pending or failed exports cannot be downloaded', function () {
    $manager = makeExportUser(SystemRole::MANAGER->value);

    $pendingExport = KpiReportExport::factory()->create([
        'requested_by' => $manager->getKey(),
        'status' => KpiReportExportStatus::PENDING,
        'disk' => 'local',
        'file_path' => 'exports/pending.xlsx',
        'file_name' => 'pending.xlsx',
    ]);
    $failedExport = KpiReportExport::factory()->failed()->create([
        'requested_by' => $manager->getKey(),
        'disk' => 'local',
        'file_path' => 'exports/failed.xlsx',
        'file_name' => 'failed.xlsx',
    ]);

    Storage::disk('local')->put('exports/pending.xlsx', 'pending');
    Storage::disk('local')->put('exports/failed.xlsx', 'failed');

    actingAs($manager);

    get(route('kpi-report-exports.download', $pendingExport))->assertForbidden();
    get(route('kpi-report-exports.download', $failedExport))->assertForbidden();
});

it('missing export file returns a safe 404 response', function () {
    $manager = makeExportUser(SystemRole::MANAGER->value);

    $export = KpiReportExport::factory()->completed()->create([
        'requested_by' => $manager->getKey(),
        'disk' => 'local',
        'file_path' => 'exports/missing.xlsx',
        'file_name' => 'missing.xlsx',
    ]);

    actingAs($manager);

    get(route('kpi-report-exports.download', $export))->assertNotFound();
});
