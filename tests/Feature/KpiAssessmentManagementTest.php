<?php

use App\Actions\KpiAssessments\CalculateKpiAssessmentScoreAction;
use App\Actions\KpiAssessments\CreateKpiAssessmentAction;
use App\Actions\KpiAssessments\SubmitKpiAssessmentAction;
use App\Actions\KpiAssessments\UpdateKpiAssessmentItemAction;
use App\Actions\KpiAssessments\UpdateKpiAttendanceAdjustmentAction;
use App\Enums\KpiAssessmentStatus;
use App\Enums\KpiAssignmentStatus;
use App\Enums\SystemRole;
use App\Events\KpiAssessmentSubmitted;
use App\Models\KpiAssignment;
use App\Models\KpiPeriod;
use App\Models\KpiTemplate;
use App\Models\KpiTemplateItem;
use App\Models\User;
use App\Policies\KpiAssessmentPolicy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makeAssessmentUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function makeAssessmentTemplate(int $itemCount = 2): KpiTemplate
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
            'name' => "Item {$index}",
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

function makeAssessmentAssignment(User $employee, array $attributes = []): KpiAssignment
{
    $period = KpiPeriod::factory()->yearly()->create(['is_active' => true]);
    $template = makeAssessmentTemplate();

    return KpiAssignment::factory()->create(array_merge([
        'kpi_period_id' => $period->getKey(),
        'kpi_template_id' => $template->getKey(),
        'employee_id' => $employee->getKey(),
        'status' => KpiAssignmentStatus::ASSIGNED->value,
        'assigned_at' => now(),
        'cancelled_at' => null,
    ], $attributes));
}

it('guest cannot access the assessment admin page', function () {
    get('/admin/kpi-assessments')->assertRedirect('/admin/login');
});

it('manager can create assessment for a direct subordinate assignment', function () {
    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);

    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);

    expect($assessment->employee_id)->toBe($employee->getKey())
        ->and($assessment->assessor_id)->toBe($manager->getKey())
        ->and($assessment->status)->toBe(KpiAssessmentStatus::DRAFT)
        ->and($assessment->items)->toHaveCount(2)
        ->and($assessment->attendanceAdjustment)->not->toBeNull();
});

it('manager cannot create assessment for unrelated employee', function () {
    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);

    expect(fn () => app(CreateKpiAssessmentAction::class)->execute($assignment, $manager))
        ->toThrow(AuthorizationException::class);
});

it('employee cannot create assessment', function () {
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($employee);

    expect(fn () => app(CreateKpiAssessmentAction::class)->execute($assignment, $employee))
        ->toThrow(AuthorizationException::class);
});

it('hrd can view an assessment in the admin panel', function () {
    $hrd = makeAssessmentUser(SystemRole::HRD->value);
    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);

    actingAs($hrd);

    get("/admin/kpi-assessments/{$assessment->getKey()}/edit")->assertOk();
});

it('cancelled assignment cannot be assessed', function () {
    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee, [
        'status' => KpiAssignmentStatus::CANCELLED->value,
        'cancelled_at' => now(),
    ]);

    actingAs($manager);

    expect(fn () => app(CreateKpiAssessmentAction::class)->execute($assignment, $manager))
        ->toThrow(ValidationException::class);
});

it('draft assignment cannot be assessed', function () {
    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee, [
        'status' => KpiAssignmentStatus::DRAFT->value,
        'assigned_at' => null,
    ]);

    actingAs($manager);

    expect(fn () => app(CreateKpiAssessmentAction::class)->execute($assignment, $manager))
        ->toThrow(ValidationException::class);
});

it('duplicate assessment for the same assignment is rejected', function () {
    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);
    app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);

    expect(fn () => app(CreateKpiAssessmentAction::class)->execute($assignment->fresh(), $manager))
        ->toThrow(ValidationException::class);
});

it('assessment creation copies published template items', function () {
    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $template = makeAssessmentTemplate(3);
    $assignment = makeAssessmentAssignment($employee, [
        'kpi_template_id' => $template->getKey(),
    ]);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment->fresh(), $manager);

    expect($assessment->items->pluck('kpi_template_item_id')->sort()->values()->all())
        ->toBe($template->items->pluck('id')->sort()->values()->all());
});

it('assessment creation snapshots template item fields for historical display and calculation', function () {
    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $template = makeAssessmentTemplate(1);
    $templateItem = $template->items->firstOrFail();
    $assignment = makeAssessmentAssignment($employee, [
        'kpi_template_id' => $template->getKey(),
    ]);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment->fresh(), $manager);
    $item = $assessment->items()->firstOrFail();

    expect($item->template_item_name)->toBe($templateItem->name)
        ->and($item->template_item_description)->toBe($templateItem->description)
        ->and($item->template_item_weight)->toBe($templateItem->weight)
        ->and($item->template_item_target_description)->toBe($templateItem->target_description)
        ->and($item->template_item_data_source)->toBe($templateItem->data_source)
        ->and($item->template_item_is_required)->toBe($templateItem->is_required);
});

it('assessment item update accepts actual value and evidence note and calculates weighted score', function () {
    Storage::fake(config('pulsekpi.assessments.evidence_disk', 'local'));

    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);
    $item = $assessment->items()->with('templateItem')->firstOrFail();

    app(UpdateKpiAssessmentItemAction::class)->execute($item, [
        'item_id' => $item->getKey(),
        'actual_value' => '12.50',
        'score' => 1,
        'evidence_note' => 'Quarterly summary uploaded.',
        'evidence_file' => UploadedFile::fake()->create('evidence.pdf', 100, 'application/pdf'),
    ], $manager);

    $item->refresh();
    $assessment->refresh();

    expect($item->actual_value)->toBe('12.50')
        ->and($item->evidence_note)->toBe('Quarterly summary uploaded.')
        ->and($item->weighted_score)->toBe('30.00')
        ->and($assessment->kpi_score)->toBe('30.00');

    Storage::disk(config('pulsekpi.assessments.evidence_disk', 'local'))
        ->assertExists($item->evidence_file_path);
});

it('invalid assessment item score is rejected', function () {
    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);
    $item = $assessment->items()->firstOrFail();

    expect(fn () => app(UpdateKpiAssessmentItemAction::class)->execute($item, [
        'item_id' => $item->getKey(),
        'score' => 3,
    ], $manager))->toThrow(ValidationException::class);
});

it('score zero is accepted as an intentional assessment score', function () {
    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);
    $items = $assessment->items()->get();

    app(UpdateKpiAssessmentItemAction::class)->execute($items[0], [
        'item_id' => $items[0]->getKey(),
        'score' => 0,
    ], $manager);

    app(UpdateKpiAssessmentItemAction::class)->execute($items[1], [
        'item_id' => $items[1]->getKey(),
        'score' => 2,
    ], $manager);

    $submitted = app(SubmitKpiAssessmentAction::class)->execute($assessment, $manager);

    expect($submitted->status)->toBe(KpiAssessmentStatus::SUBMITTED)
        ->and($submitted->items()->findOrFail($items[0]->getKey())->score)->toBe(0);
});

it('attendance deduction and score are calculated correctly and leave days do not reduce score', function () {
    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);

    foreach ($assessment->items as $item) {
        app(UpdateKpiAssessmentItemAction::class)->execute($item, [
            'item_id' => $item->getKey(),
            'score' => 2,
        ], $manager);
    }

    app(UpdateKpiAttendanceAdjustmentAction::class)->execute($assessment->attendanceAdjustment, [
        'working_days' => 26,
        'sick_days' => 2,
        'permission_days' => 1,
        'absent_days' => 3,
        'leave_days' => 4,
    ], $manager);

    $assessment->refresh();
    $attendance = $assessment->attendanceAdjustment->refresh();

    expect($attendance->deduction_score)->toBe('2.50')
        ->and($attendance->attendance_score)->toBe('97.50')
        ->and($assessment->attendance_deduction)->toBe('2.50')
        ->and($assessment->attendance_score)->toBe('97.50')
        ->and($assessment->final_score)->toBe('97.50');
});

it('negative attendance values and excessive non working days are rejected', function () {
    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);

    expect(fn () => app(UpdateKpiAttendanceAdjustmentAction::class)->execute($assessment->attendanceAdjustment, [
        'working_days' => 26,
        'sick_days' => -1,
        'permission_days' => 0,
        'absent_days' => 0,
        'leave_days' => 0,
    ], $manager))->toThrow(ValidationException::class);

    expect(fn () => app(UpdateKpiAttendanceAdjustmentAction::class)->execute($assessment->attendanceAdjustment, [
        'working_days' => 2,
        'sick_days' => 1,
        'permission_days' => 1,
        'absent_days' => 1,
        'leave_days' => 0,
    ], $manager))->toThrow(ValidationException::class);
});

it('final score and grade are calculated correctly before submit', function () {
    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);
    $items = $assessment->items()->with('templateItem')->get();

    app(UpdateKpiAssessmentItemAction::class)->execute($items[0], [
        'item_id' => $items[0]->getKey(),
        'score' => 2,
    ], $manager);

    app(UpdateKpiAssessmentItemAction::class)->execute($items[1], [
        'item_id' => $items[1]->getKey(),
        'score' => 1,
    ], $manager);

    app(UpdateKpiAttendanceAdjustmentAction::class)->execute($assessment->attendanceAdjustment, [
        'working_days' => 26,
        'sick_days' => 1,
        'permission_days' => 0,
        'absent_days' => 0,
        'leave_days' => 0,
    ], $manager);

    $assessment->refresh();

    expect($assessment->kpi_score)->toBe('80.00')
        ->and($assessment->attendance_score)->toBe('99.90')
        ->and($assessment->final_score)->toBe('79.90')
        ->and($assessment->grade)->toBe('Fair');
});

it('historical assessment calculation remains stable if template item changes later', function () {
    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $template = makeAssessmentTemplate(1);
    $assignment = makeAssessmentAssignment($employee, [
        'kpi_template_id' => $template->getKey(),
    ]);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment->fresh(), $manager);
    $item = $assessment->items()->firstOrFail();

    app(UpdateKpiAssessmentItemAction::class)->execute($item, [
        'item_id' => $item->getKey(),
        'score' => 2,
    ], $manager);

    $templateItem = $template->items()->firstOrFail();
    $templateItem->forceFill([
        'name' => 'Changed name',
        'weight' => '10.00',
        'target_description' => 'Changed target',
        'data_source' => 'Changed source',
    ])->saveQuietly();

    $recalculated = app(CalculateKpiAssessmentScoreAction::class)
        ->execute($assessment->fresh());
    $snapshotItem = $recalculated->items()->firstOrFail();

    expect($snapshotItem->template_item_name)->not->toBe('Changed name')
        ->and($snapshotItem->template_item_weight)->toBe('100.00')
        ->and($recalculated->kpi_score)->toBe('100.00');
});

it('blank assessment cannot be submitted', function () {
    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);

    expect(fn () => app(SubmitKpiAssessmentAction::class)->execute($assessment, $manager))
        ->toThrow(ValidationException::class);
});

it('all required items must be scored before submit', function () {
    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);
    $firstItem = $assessment->items()->firstOrFail();

    app(UpdateKpiAssessmentItemAction::class)->execute($firstItem, [
        'item_id' => $firstItem->getKey(),
        'score' => 2,
    ], $manager);

    expect(fn () => app(SubmitKpiAssessmentAction::class)->execute($assessment->fresh('items'), $manager))
        ->toThrow(ValidationException::class);
});

it('manager can submit a draft assessment and submission dispatches audit and event', function () {
    Event::fake([KpiAssessmentSubmitted::class]);

    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);

    foreach ($assessment->items as $item) {
        app(UpdateKpiAssessmentItemAction::class)->execute($item, [
            'item_id' => $item->getKey(),
            'score' => 2,
        ], $manager);
    }

    $submitted = app(SubmitKpiAssessmentAction::class)->execute($assessment, $manager);

    expect($submitted->status)->toBe(KpiAssessmentStatus::SUBMITTED)
        ->and($submitted->submitted_at)->not->toBeNull();

    Event::assertDispatched(KpiAssessmentSubmitted::class);

    $this->assertDatabaseHas('activity_log', [
        'event' => 'kpi_assessment.submitted',
    ]);
});

it('submitted assessment cannot be edited freely', function () {
    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);

    foreach ($assessment->items as $item) {
        app(UpdateKpiAssessmentItemAction::class)->execute($item, [
            'item_id' => $item->getKey(),
            'score' => 2,
        ], $manager);
    }

    app(SubmitKpiAssessmentAction::class)->execute($assessment, $manager);

    expect(fn () => app(UpdateKpiAssessmentItemAction::class)->execute($assessment->items()->firstOrFail(), [
        'item_id' => $assessment->items()->firstOrFail()->getKey(),
        'score' => 1,
    ], $manager))->toThrow(AuthorizationException::class);
});

it('evidence download is authorized for manager owner and denied to unauthorized users', function () {
    Storage::fake(config('pulsekpi.assessments.evidence_disk', 'local'));

    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $otherEmployee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $hrd = makeAssessmentUser(SystemRole::HRD->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);
    $item = $assessment->items()->firstOrFail();

    app(UpdateKpiAssessmentItemAction::class)->execute($item, [
        'item_id' => $item->getKey(),
        'score' => 2,
        'evidence_file' => UploadedFile::fake()->create('evidence.pdf', 100, 'application/pdf'),
    ], $manager);

    $item->refresh();

    actingAs($otherEmployee);
    get(route('kpi-assessment-items.evidence.download', ['item' => $item]))->assertForbidden();

    actingAs($manager);
    get(route('kpi-assessment-items.evidence.download', ['item' => $item]))->assertOk();

    actingAs($employee);
    get(route('kpi-assessment-items.evidence.download', ['item' => $item]))->assertOk();

    actingAs($hrd);
    get(route('kpi-assessment-items.evidence.download', ['item' => $item]))->assertOk();
});

it('super admin can download evidence for any assessment', function () {
    Storage::fake(config('pulsekpi.assessments.evidence_disk', 'local'));

    $superAdmin = makeAssessmentUser(SystemRole::SUPER_ADMIN->value);
    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);
    $item = $assessment->items()->firstOrFail();

    app(UpdateKpiAssessmentItemAction::class)->execute($item, [
        'item_id' => $item->getKey(),
        'score' => 2,
        'evidence_file' => UploadedFile::fake()->create('evidence.pdf', 100, 'application/pdf'),
    ], $manager);

    $item->refresh();

    actingAs($superAdmin);
    get(route('kpi-assessment-items.evidence.download', ['item' => $item]))->assertOk();
});

it('unrelated employee cannot download another employee evidence', function () {
    Storage::fake(config('pulsekpi.assessments.evidence_disk', 'local'));

    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $unrelated = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);
    $item = $assessment->items()->firstOrFail();

    app(UpdateKpiAssessmentItemAction::class)->execute($item, [
        'item_id' => $item->getKey(),
        'score' => 2,
        'evidence_file' => UploadedFile::fake()->create('secret.pdf', 100, 'application/pdf'),
    ], $manager);

    $item->refresh();

    actingAs($unrelated);
    get(route('kpi-assessment-items.evidence.download', ['item' => $item]))->assertForbidden();
});

it('replacing evidence deletes the old private file', function () {
    Storage::fake(config('pulsekpi.assessments.evidence_disk', 'local'));

    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);
    $item = $assessment->items()->firstOrFail();

    app(UpdateKpiAssessmentItemAction::class)->execute($item, [
        'item_id' => $item->getKey(),
        'score' => 1,
        'evidence_file' => UploadedFile::fake()->create('first.pdf', 100, 'application/pdf'),
    ], $manager);

    $item->refresh();
    $firstPath = $item->evidence_file_path;

    app(UpdateKpiAssessmentItemAction::class)->execute($item->fresh(), [
        'item_id' => $item->getKey(),
        'score' => 2,
        'evidence_file' => UploadedFile::fake()->create('second.pdf', 100, 'application/pdf'),
    ], $manager);

    $item->refresh();

    Storage::disk(config('pulsekpi.assessments.evidence_disk', 'local'))->assertMissing($firstPath);
    Storage::disk(config('pulsekpi.assessments.evidence_disk', 'local'))->assertExists($item->evidence_file_path);
});

it('invalid evidence mime type and oversized upload are rejected', function () {
    Storage::fake(config('pulsekpi.assessments.evidence_disk', 'local'));

    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);
    $item = $assessment->items()->firstOrFail();

    expect(fn () => app(UpdateKpiAssessmentItemAction::class)->execute($item, [
        'item_id' => $item->getKey(),
        'score' => 1,
        'evidence_file' => UploadedFile::fake()->create('notes.txt', 20, 'text/plain'),
    ], $manager))->toThrow(ValidationException::class);

    expect(fn () => app(UpdateKpiAssessmentItemAction::class)->execute($item, [
        'item_id' => $item->getKey(),
        'score' => 1,
        'evidence_file' => UploadedFile::fake()->create('large.pdf', 6000, 'application/pdf'),
    ], $manager))->toThrow(ValidationException::class);
});

it('policy allows manager and employee view access only within scope', function () {
    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $otherManager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $otherEmployee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);

    $policy = new KpiAssessmentPolicy;

    expect($policy->view($manager, $assessment))->toBeTrue()
        ->and($policy->view($otherManager, $assessment))->toBeFalse()
        ->and($policy->view($employee, $assessment))->toBeTrue()
        ->and($policy->view($otherEmployee, $assessment))->toBeFalse();
});

it('employee can view their own read only assessment page', function () {
    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);

    actingAs($employee);
    get(route('my.kpi-assessments.show', ['kpiAssessment' => $assessment]))->assertOk();
});

it('employee cannot view another employees assessment page', function () {
    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $otherEmployee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);

    actingAs($otherEmployee);
    get(route('my.kpi-assessments.show', ['kpiAssessment' => $assessment]))->assertForbidden();
});

it('guest is redirected to login for the employee assessment page', function () {
    $manager = makeAssessmentUser(SystemRole::MANAGER->value);
    $employee = makeAssessmentUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $manager->getKey()]);
    $assignment = makeAssessmentAssignment($employee);

    actingAs($manager);
    $assessment = app(CreateKpiAssessmentAction::class)->execute($assignment, $manager);

    auth()->logout();

    get(route('my.kpi-assessments.show', ['kpiAssessment' => $assessment]))->assertRedirect('/login');
});
