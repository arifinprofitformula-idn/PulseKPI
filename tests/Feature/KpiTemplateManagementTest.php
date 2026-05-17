<?php

use App\Actions\KpiTemplates\DuplicateKpiTemplateAction;
use App\Actions\KpiTemplates\PublishKpiTemplateAction;
use App\Enums\SystemRole;
use App\Filament\Resources\KpiTemplates\Pages\CreateKpiTemplate;
use App\Filament\Resources\KpiTemplates\Pages\EditKpiTemplate;
use App\Filament\Resources\KpiTemplates\RelationManagers\KpiTemplateItemsRelationManager;
use App\Models\KpiScoreRule;
use App\Models\KpiTemplate;
use App\Models\KpiTemplateItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function actingAsRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    test()->actingAs($user);

    return $user;
}

function createTemplateItemWithRules(KpiTemplate $template, string $weight = '100.00'): KpiTemplateItem
{
    $item = KpiTemplateItem::factory()->create([
        'kpi_template_id' => $template->getKey(),
        'sort_order' => 1,
        'weight' => $weight,
    ]);

    foreach ([0, 1, 2] as $score) {
        KpiScoreRule::factory()->create([
            'kpi_template_item_id' => $item->getKey(),
            'score' => $score,
            'label' => "Score {$score}",
        ]);
    }

    return $item;
}

it('hrd can create a kpi template', function () {
    actingAsRole(SystemRole::HRD->value);

    Livewire::test(CreateKpiTemplate::class)
        ->fillForm([
            'name' => 'Annual Sales KPI',
            'code' => 'KPI-SALES-2026',
            'year' => 2026,
            'revision' => '00',
            'description' => 'Sales performance baseline.',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(KpiTemplate::class, [
        'name' => 'Annual Sales KPI',
        'code' => 'KPI-SALES-2026',
        'year' => 2026,
        'revision' => '00',
    ]);
});

it('employee cannot create kpi templates', function () {
    actingAsRole(SystemRole::EMPLOYEE->value);

    $this->get('/admin/kpi-templates/create')->assertForbidden();
});

it('hrd can add a kpi item', function () {
    actingAsRole(SystemRole::HRD->value);
    $template = KpiTemplate::factory()->create();

    Livewire::test(KpiTemplateItemsRelationManager::class, [
        'ownerRecord' => $template,
        'pageClass' => EditKpiTemplate::class,
    ])
        ->callTableAction('create', data: [
            'sort_order' => 1,
            'name' => 'Close deals on time',
            'description' => 'Monthly sales close rate.',
            'weight' => '60.00',
            'target_description' => 'Maintain close rate above target.',
            'data_source' => 'CRM',
            'is_required' => true,
        ])
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(KpiTemplateItem::class, [
        'kpi_template_id' => $template->getKey(),
        'name' => 'Close deals on time',
        'weight' => '60.00',
    ]);
});

it('rejects total item weight over 100', function () {
    actingAsRole(SystemRole::HRD->value);
    $template = KpiTemplate::factory()->create();

    KpiTemplateItem::factory()->create([
        'kpi_template_id' => $template->getKey(),
        'weight' => '60.00',
    ]);

    Livewire::test(KpiTemplateItemsRelationManager::class, [
        'ownerRecord' => $template,
        'pageClass' => EditKpiTemplate::class,
    ])
        ->callTableAction('create', data: [
            'sort_order' => 2,
            'name' => 'Upsell existing accounts',
            'weight' => '50.01',
            'target_description' => 'Expand annual recurring revenue.',
            'is_required' => true,
        ]);

    $this->assertDatabaseCount(KpiTemplateItem::class, 1);
    $this->assertDatabaseMissing(KpiTemplateItem::class, [
        'kpi_template_id' => $template->getKey(),
        'name' => 'Upsell existing accounts',
    ]);
});

it('template cannot be published if total weight is not exactly 100', function () {
    $template = KpiTemplate::factory()->create();
    KpiTemplateItem::factory()->create([
        'kpi_template_id' => $template->getKey(),
        'weight' => '80.00',
    ]);

    expect(fn () => app(PublishKpiTemplateAction::class)->execute($template))
        ->toThrow(ValidationException::class);

    expect($template->fresh()->published_at)->toBeNull();
});

it('template cannot be published without items', function () {
    $template = KpiTemplate::factory()->create();

    expect(fn () => app(PublishKpiTemplateAction::class)->execute($template))
        ->toThrow(ValidationException::class);

    expect($template->fresh()->published_at)->toBeNull();
});

it('template can be published when valid', function () {
    actingAsRole(SystemRole::HRD->value);
    $template = KpiTemplate::factory()->create();
    createTemplateItemWithRules($template);

    $published = app(PublishKpiTemplateAction::class)->execute($template);

    expect($published->published_at)->not->toBeNull();
    $this->assertDatabaseHas('activity_log', [
        'event' => 'kpi_template.published',
        'subject_type' => KpiTemplate::class,
        'subject_id' => $template->getKey(),
    ]);
});

it('published template cannot be edited by hrd', function () {
    actingAsRole(SystemRole::HRD->value);
    $template = KpiTemplate::factory()->published()->create();

    $this->get("/admin/kpi-templates/{$template->getRouteKey()}/edit")
        ->assertForbidden();
});

it('hrd can duplicate a template to a new revision', function () {
    $source = KpiTemplate::factory()->create([
        'code' => 'KPI-SALES-2026',
        'revision' => '00',
    ]);

    $duplicate = app(DuplicateKpiTemplateAction::class)->execute($source, [
        'code' => 'KPI-SALES-2026-R01',
        'revision' => '01',
    ]);

    expect($duplicate->revision)->toBe('01')
        ->and($duplicate->code)->toBe('KPI-SALES-2026-R01')
        ->and($duplicate->is_active)->toBeFalse()
        ->and($duplicate->published_at)->toBeNull();
});

it('duplicate copies items and score rules', function () {
    actingAsRole(SystemRole::HRD->value);
    $source = KpiTemplate::factory()->create([
        'code' => 'KPI-OPS-2026',
    ]);
    createTemplateItemWithRules($source, '100.00');

    $duplicate = app(DuplicateKpiTemplateAction::class)->execute($source, [
        'code' => 'KPI-OPS-2027',
        'year' => 2027,
    ]);

    expect($duplicate->items)->toHaveCount(1)
        ->and($duplicate->items->first()->scoreRules)->toHaveCount(3)
        ->and($duplicate->year)->toBe(2027);

    $this->assertDatabaseHas('activity_log', [
        'event' => 'kpi_template.duplicated',
        'subject_type' => KpiTemplate::class,
        'subject_id' => $duplicate->getKey(),
    ]);
});

it('manager can view an active published template', function () {
    actingAsRole(SystemRole::MANAGER->value);
    $template = KpiTemplate::factory()->create([
        'is_active' => true,
    ]);
    createTemplateItemWithRules($template);
    $template->update(['published_at' => now()]);

    $this->get("/admin/kpi-templates/{$template->getRouteKey()}")
        ->assertOk();
});

it('manager cannot view a draft template', function () {
    actingAsRole(SystemRole::MANAGER->value);
    $template = KpiTemplate::factory()->create([
        'published_at' => null,
        'is_active' => true,
    ]);

    $this->get("/admin/kpi-templates/{$template->getRouteKey()}")
        ->assertNotFound();
});

it('manager cannot view an inactive published template', function () {
    actingAsRole(SystemRole::MANAGER->value);
    $template = KpiTemplate::factory()->create([
        'is_active' => false,
        'published_at' => now(),
    ]);

    $this->get("/admin/kpi-templates/{$template->getRouteKey()}")
        ->assertNotFound();
});

it('hrd cannot add an item to a published template', function () {
    actingAsRole(SystemRole::HRD->value);
    $template = KpiTemplate::factory()->published()->create();

    Livewire::test(KpiTemplateItemsRelationManager::class, [
        'ownerRecord' => $template,
        'pageClass' => EditKpiTemplate::class,
    ])
        ->assertTableActionDisabled('create');
});

it('hrd cannot edit an item under a published template', function () {
    actingAsRole(SystemRole::HRD->value);
    $template = KpiTemplate::factory()->create();
    $item = KpiTemplateItem::factory()->create([
        'kpi_template_id' => $template->getKey(),
    ]);
    $template->update(['published_at' => now()]);

    Livewire::test(KpiTemplateItemsRelationManager::class, [
        'ownerRecord' => $template,
        'pageClass' => EditKpiTemplate::class,
    ])
        ->assertTableActionDisabled('edit', $item);
});

it('hrd cannot delete an item under a published template', function () {
    actingAsRole(SystemRole::HRD->value);
    $template = KpiTemplate::factory()->create();
    $item = KpiTemplateItem::factory()->create([
        'kpi_template_id' => $template->getKey(),
    ]);
    $template->update(['published_at' => now()]);

    Livewire::test(KpiTemplateItemsRelationManager::class, [
        'ownerRecord' => $template,
        'pageClass' => EditKpiTemplate::class,
    ])
        ->assertTableActionDisabled('delete', $item);
});

it('hrd cannot create a score rule under a published template item', function () {
    actingAsRole(SystemRole::HRD->value);
    $template = KpiTemplate::factory()->create();
    $item = KpiTemplateItem::factory()->create([
        'kpi_template_id' => $template->getKey(),
    ]);
    $scoreRule = KpiScoreRule::factory()->create([
        'kpi_template_item_id' => $item->getKey(),
        'score' => 1,
    ]);
    $template->update(['published_at' => now()]);

    expect(fn () => $item->scoreRules()->create([
        'score' => 0,
        'label' => 'Below target',
    ]))->toThrow(ValidationException::class);
});

it('hrd cannot edit a score rule under a published template item', function () {
    actingAsRole(SystemRole::HRD->value);
    $template = KpiTemplate::factory()->create();
    $item = KpiTemplateItem::factory()->create([
        'kpi_template_id' => $template->getKey(),
    ]);
    $scoreRule = KpiScoreRule::factory()->create([
        'kpi_template_item_id' => $item->getKey(),
        'score' => 0,
    ]);
    $template->update(['published_at' => now()]);

    expect(fn () => $scoreRule->update([
        'label' => 'Updated label',
    ]))->toThrow(ValidationException::class);
});

it('hrd cannot delete a score rule under a published template item', function () {
    actingAsRole(SystemRole::HRD->value);
    $template = KpiTemplate::factory()->create();
    $item = KpiTemplateItem::factory()->create([
        'kpi_template_id' => $template->getKey(),
    ]);
    $scoreRule = KpiScoreRule::factory()->create([
        'kpi_template_item_id' => $item->getKey(),
        'score' => 0,
    ]);
    $template->update(['published_at' => now()]);

    expect(fn () => $scoreRule->delete())->toThrow(ValidationException::class);
});

it('invalid score value such as 3 is rejected', function () {
    $item = KpiTemplateItem::factory()->create();

    expect(fn () => $item->scoreRules()->create([
        'score' => 3,
        'label' => 'Invalid',
    ]))->toThrow(ValidationException::class);
});

it('duplicate score per item is rejected', function () {
    $item = KpiTemplateItem::factory()->create();
    KpiScoreRule::factory()->create([
        'kpi_template_item_id' => $item->getKey(),
        'score' => 1,
    ]);

    expect(fn () => $item->scoreRules()->create([
        'score' => 1,
        'label' => 'Duplicate',
    ]))->toThrow(ValidationException::class);
});

it('updating template item creates an audit log', function () {
    actingAsRole(SystemRole::HRD->value);
    $item = KpiTemplateItem::factory()->create();

    $item->update([
        'name' => 'Updated KPI Component',
    ]);

    $this->assertDatabaseHas('activity_log', [
        'event' => 'kpi_template_item.updated',
        'subject_type' => KpiTemplateItem::class,
        'subject_id' => $item->getKey(),
    ]);
});

it('updating score rule creates an audit log', function () {
    actingAsRole(SystemRole::HRD->value);
    $scoreRule = KpiScoreRule::factory()->create([
        'score' => 0,
    ]);

    $scoreRule->update([
        'label' => 'Updated rule',
    ]);

    $this->assertDatabaseHas('activity_log', [
        'event' => 'kpi_score_rule.updated',
        'subject_type' => KpiScoreRule::class,
        'subject_id' => $scoreRule->getKey(),
    ]);
});

it('original template remains unchanged after duplication', function () {
    actingAsRole(SystemRole::HRD->value);
    $source = KpiTemplate::factory()->create([
        'code' => 'KPI-FIN-2026',
        'revision' => '00',
        'is_active' => true,
    ]);
    createTemplateItemWithRules($source);
    $source->update(['published_at' => now()]);

    app(DuplicateKpiTemplateAction::class)->execute($source, [
        'code' => 'KPI-FIN-2026-R01',
        'revision' => '01',
    ]);

    $source->refresh();

    expect($source->code)->toBe('KPI-FIN-2026')
        ->and($source->revision)->toBe('00')
        ->and($source->is_active)->toBeTrue()
        ->and($source->published_at)->not->toBeNull()
        ->and($source->items)->toHaveCount(1);
});

it('employee cannot manage templates', function () {
    actingAsRole(SystemRole::EMPLOYEE->value);
    $template = KpiTemplate::factory()->create();

    $this->get('/admin/kpi-templates')->assertForbidden();
    $this->get("/admin/kpi-templates/{$template->getRouteKey()}/edit")->assertForbidden();
});

it('hrd can access the kpi template list page', function () {
    actingAsRole(SystemRole::HRD->value);

    $this->get('/admin/kpi-templates')
        ->assertOk()
        ->assertSee('KPI Templates');
});

it('dashboard widget loads for hrd users', function () {
    actingAsRole(SystemRole::HRD->value);

    $this->get('/admin')
        ->assertOk()
    ->assertSee('Dashboard');
});

it('template resources require authentication', function () {
    $template = KpiTemplate::factory()->create();

    $this->get('/admin/kpi-templates')->assertRedirect('/admin/login');
    $this->get('/admin/kpi-templates/create')->assertRedirect('/admin/login');
    $this->get("/admin/kpi-templates/{$template->getRouteKey()}")->assertRedirect('/admin/login');
});
