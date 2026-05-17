<?php

use App\Enums\KpiPeriodType;
use App\Enums\SystemRole;
use App\Filament\Resources\KpiPeriods\Pages\CreateKpiPeriod;
use App\Filament\Resources\KpiPeriods\Pages\EditKpiPeriod;
use App\Filament\Resources\KpiPeriods\Pages\ListKpiPeriods;
use App\Models\KpiPeriod;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function actingAsKpiPeriodRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

// ─── HRD can create monthly period ───────────────────────────────────────────

it('hrd can create a monthly kpi period', function () {
    actingAsKpiPeriodRole(SystemRole::HRD->value);

    Livewire::test(CreateKpiPeriod::class)
        ->fillForm([
            'name' => 'January 2026',
            'type' => KpiPeriodType::MONTHLY->value,
            'month' => 1,
            'year' => 2026,
            'starts_at' => '2026-01-01',
            'ends_at' => '2026-01-31',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(KpiPeriod::query()->where('name', 'January 2026')->exists())->toBeTrue();
});

// ─── Monthly period requires month ────────────────────────────────────────────

it('monthly period requires month field', function () {
    actingAsKpiPeriodRole(SystemRole::HRD->value);

    Livewire::test(CreateKpiPeriod::class)
        ->fillForm([
            'name' => 'January 2026',
            'type' => KpiPeriodType::MONTHLY->value,
            'month' => null,
            'year' => 2026,
            'starts_at' => '2026-01-01',
            'ends_at' => '2026-01-31',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['month']);
});

// ─── Yearly period does not require month ────────────────────────────────────

it('hrd can create a yearly kpi period without month', function () {
    actingAsKpiPeriodRole(SystemRole::HRD->value);

    Livewire::test(CreateKpiPeriod::class)
        ->fillForm([
            'name' => '2026',
            'type' => KpiPeriodType::YEARLY->value,
            'month' => null,
            'year' => 2026,
            'starts_at' => '2026-01-01',
            'ends_at' => '2026-12-31',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(KpiPeriod::query()->where('name', '2026')->exists())->toBeTrue();
});

// ─── starts_at must be before or equal to ends_at ────────────────────────────

it('starts_at must be before or equal to ends_at', function () {
    actingAsKpiPeriodRole(SystemRole::HRD->value);

    Livewire::test(CreateKpiPeriod::class)
        ->fillForm([
            'name' => 'Invalid Period',
            'type' => KpiPeriodType::MONTHLY->value,
            'month' => 1,
            'year' => 2026,
            'starts_at' => '2026-01-31',
            'ends_at' => '2026-01-01',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['ends_at']);
});

// ─── Employee cannot create period ───────────────────────────────────────────

it('employee cannot access kpi period create page', function () {
    actingAsKpiPeriodRole(SystemRole::EMPLOYEE->value);

    $this->get('/admin/kpi-periods/create')->assertForbidden();
});

// ─── HRD can list periods ──────────────────────────────────────────────────────

it('hrd can view kpi periods list', function () {
    actingAsKpiPeriodRole(SystemRole::HRD->value);

    KpiPeriod::factory()->count(3)->create();

    Livewire::test(ListKpiPeriods::class)
        ->assertSuccessful();
});

// ─── HRD can edit a period ─────────────────────────────────────────────────────

it('hrd can edit a kpi period', function () {
    actingAsKpiPeriodRole(SystemRole::HRD->value);

    $period = KpiPeriod::factory()->yearly()->create(['name' => 'Old Name']);

    Livewire::test(EditKpiPeriod::class, ['record' => $period->getKey()])
        ->fillForm(['name' => 'Updated Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($period->fresh()->name)->toBe('Updated Name');
});

// ─── KpiPeriodObserver logs creation ─────────────────────────────────────────

it('kpi_period.created audit log is recorded', function () {
    actingAsKpiPeriodRole(SystemRole::HRD->value);

    Livewire::test(CreateKpiPeriod::class)
        ->fillForm([
            'name' => 'Audit Test Period',
            'type' => KpiPeriodType::YEARLY->value,
            'month' => null,
            'year' => 2026,
            'starts_at' => '2026-01-01',
            'ends_at' => '2026-12-31',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('activity_log', [
        'event' => 'kpi_period.created',
    ]);
});
