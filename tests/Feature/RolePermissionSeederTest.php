<?php

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('seeds the expected roles and permissions', function () {
    $this->seed(RolePermissionSeeder::class);

    expect(Role::query()->pluck('name')->all())
        ->toContain(
            SystemRole::SUPER_ADMIN->value,
            SystemRole::HRD->value,
            SystemRole::MANAGER->value,
            SystemRole::SUPERVISOR->value,
            SystemRole::EMPLOYEE->value,
            SystemRole::APPROVER->value,
        );

    expect(Permission::query()->pluck('name')->all())
        ->toContain(
            SystemPermission::ACCESS_ADMIN_PANEL->value,
            SystemPermission::MANAGE_USERS->value,
            SystemPermission::MANAGE_ORGANIZATION->value,
            SystemPermission::MANAGE_KPI_TEMPLATES->value,
            SystemPermission::ASSIGN_KPI->value,
            SystemPermission::SUBMIT_KPI_ASSESSMENT->value,
            SystemPermission::REVIEW_KPI_ASSESSMENT->value,
            SystemPermission::APPROVE_KPI_ASSESSMENT->value,
            SystemPermission::VIEW_REPORTS->value,
            SystemPermission::EXPORT_REPORTS->value,
        );

    expect(Role::findByName(SystemRole::SUPER_ADMIN->value)->permissions)
        ->toHaveCount(count(config('pulsekpi.permissions')));
});

it('seeds Supervisor with only admin panel and submit assessment permissions', function () {
    $this->seed(RolePermissionSeeder::class);

    $supervisor = Role::findByName(SystemRole::SUPERVISOR->value);
    $permissions = $supervisor->permissions->pluck('name')->sort()->values()->all();

    expect($permissions)->toBe([
        SystemPermission::ACCESS_ADMIN_PANEL->value,
        SystemPermission::SUBMIT_KPI_ASSESSMENT->value,
    ])->and($permissions)->not->toContain(
        SystemPermission::VIEW_REPORTS->value,
        SystemPermission::APPROVE_KPI_ASSESSMENT->value,
        SystemPermission::REVIEW_KPI_ASSESSMENT->value,
        SystemPermission::MANAGE_USERS->value,
        SystemPermission::MANAGE_ORGANIZATION->value,
        SystemPermission::ASSIGN_KPI->value,
        SystemPermission::EXPORT_REPORTS->value,
    );
});
