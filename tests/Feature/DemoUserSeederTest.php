<?php

use App\Enums\SystemRole;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\OrganizationStructureSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds a demo account for each available role', function () {
    $this->seed([
        RolePermissionSeeder::class,
        OrganizationStructureSeeder::class,
        DemoUserSeeder::class,
    ]);

    $accountsByRole = [
        SystemRole::SUPER_ADMIN->value => 'superadmin@demo.test',
        SystemRole::HRD->value => 'hrd@demo.test',
        SystemRole::MANAGER->value => 'manager@demo.test',
        SystemRole::SUPERVISOR->value => 'supervisor@demo.test',
        SystemRole::EMPLOYEE->value => 'employee@demo.test',
        SystemRole::APPROVER->value => 'approver@demo.test',
    ];

    foreach ($accountsByRole as $role => $email) {
        $user = User::query()->where('email', $email)->first();

        expect($user)
            ->not->toBeNull()
            ->and($user->hasRole($role))->toBeTrue();
    }
});

it('seeds the demo reporting hierarchy for supervisor and employee accounts', function () {
    $this->seed([
        RolePermissionSeeder::class,
        OrganizationStructureSeeder::class,
        DemoUserSeeder::class,
    ]);

    $manager = User::query()->where('email', 'manager@demo.test')->firstOrFail();
    $supervisor = User::query()->where('email', 'supervisor@demo.test')->firstOrFail();
    $employee = User::query()->where('email', 'employee@demo.test')->firstOrFail();

    expect($supervisor->supervisor_id)
        ->toBe($manager->getKey())
        ->and($employee->supervisor_id)->toBe($supervisor->getKey());
});
