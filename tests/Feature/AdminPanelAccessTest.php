<?php

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('redirects guests away from the admin panel', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('renders the admin login page with admin-specific branding copy', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee(config('branding.name'))
        ->assertSee('Akses Admin PulseKPI')
        ->assertSee('Masuk untuk melanjutkan ke panel admin dan area operasional KPI.');
});

it('allows a super admin to access the admin panel', function () {
    $user = User::factory()->create();
    $user->assignRole(SystemRole::SUPER_ADMIN->value);

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk()
        ->assertSee('KPI Command Center');
});

it('allows a user with the panel permission to access the admin panel', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(SystemPermission::ACCESS_ADMIN_PANEL->value);

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk();
});

it('allows a Supervisor to access the admin panel through seeded role permissions', function () {
    $user = User::factory()->create();
    $user->assignRole(SystemRole::SUPERVISOR->value);

    expect($user->can(SystemPermission::ACCESS_ADMIN_PANEL->value))->toBeTrue()
        ->and($user->can(SystemPermission::SUBMIT_KPI_ASSESSMENT->value))->toBeTrue()
        ->and($user->can(SystemPermission::VIEW_REPORTS->value))->toBeFalse()
        ->and($user->can(SystemPermission::REVIEW_KPI_ASSESSMENT->value))->toBeFalse()
        ->and($user->can(SystemPermission::APPROVE_KPI_ASSESSMENT->value))->toBeFalse();

    $this->actingAs($user)
        ->get('/admin')
        ->assertRedirect('/admin/supervisor-dashboard');
});

it('forbids a user without the panel permission from accessing the admin panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

it('keeps manager and approver redirects from admin root intact', function () {
    $manager = User::factory()->create();
    $manager->assignRole(SystemRole::MANAGER->value);

    $this->actingAs($manager)
        ->get('/admin')
        ->assertRedirect('/admin/manager-dashboard');

    $approver = User::factory()->create();
    $approver->assignRole(SystemRole::APPROVER->value);

    $this->actingAs($approver)
        ->get('/admin')
        ->assertRedirect('/admin/approver-dashboard');
});
