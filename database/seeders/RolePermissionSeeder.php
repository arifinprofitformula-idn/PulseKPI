<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (config('pulsekpi.permissions') as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $rolePermissions = [
            SystemRole::SUPER_ADMIN->value => config('pulsekpi.permissions'),
            SystemRole::HRD->value => [
                'access admin panel',
                'manage users',
                'manage organization',
                'manage kpi templates',
                'assign kpi',
                'review kpi assessment',
                'view reports',
                'export reports',
            ],
            SystemRole::MANAGER->value => [
                'access admin panel',
                'submit kpi assessment',
                'review kpi assessment',
                'view reports',
            ],
            SystemRole::SUPERVISOR->value => [
                'access admin panel',
                'submit kpi assessment',
            ],
            SystemRole::EMPLOYEE->value => [
                'submit kpi assessment',
            ],
            SystemRole::APPROVER->value => [
                'access admin panel',
                'approve kpi assessment',
                'view reports',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
