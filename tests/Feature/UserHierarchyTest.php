<?php

use App\Enums\SystemRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makeHierarchyUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('allows an employee to report to a Supervisor', function () {
    $supervisor = makeHierarchyUser(SystemRole::SUPERVISOR->value);
    $employee = makeHierarchyUser(SystemRole::EMPLOYEE->value);

    $employee->update(['supervisor_id' => $supervisor->getKey()]);

    expect($employee->fresh()->supervisor_id)->toBe($supervisor->getKey());
});

it('allows a Supervisor to report to a Manager', function () {
    $manager = makeHierarchyUser(SystemRole::MANAGER->value);
    $supervisor = makeHierarchyUser(SystemRole::SUPERVISOR->value);

    $supervisor->update(['supervisor_id' => $manager->getKey()]);

    expect($supervisor->fresh()->supervisor_id)->toBe($manager->getKey());
});

it('allows an employee to report directly to a Manager', function () {
    $manager = makeHierarchyUser(SystemRole::MANAGER->value);
    $employee = makeHierarchyUser(SystemRole::EMPLOYEE->value);

    $employee->update(['supervisor_id' => $manager->getKey()]);

    expect($employee->fresh()->supervisor_id)->toBe($manager->getKey());
});

it('prevents a Supervisor from reporting to an Employee', function () {
    $employee = makeHierarchyUser(SystemRole::EMPLOYEE->value);
    $supervisor = makeHierarchyUser(SystemRole::SUPERVISOR->value);

    expect(fn () => $supervisor->update(['supervisor_id' => $employee->getKey()]))
        ->toThrow(ValidationException::class);

    expect($supervisor->fresh()->supervisor_id)->toBeNull();
});

it('prevents an employee from supervising another user', function () {
    $employeeSupervisor = makeHierarchyUser(SystemRole::EMPLOYEE->value);
    $employee = makeHierarchyUser(SystemRole::EMPLOYEE->value);

    expect(fn () => $employee->update(['supervisor_id' => $employeeSupervisor->getKey()]))
        ->toThrow(ValidationException::class);

    expect($employee->fresh()->supervisor_id)->toBeNull();
});

it('prevents a direct circular reporting line', function () {
    $manager = makeHierarchyUser(SystemRole::MANAGER->value);
    $supervisor = makeHierarchyUser(SystemRole::SUPERVISOR->value);

    $supervisor->update(['supervisor_id' => $manager->getKey()]);

    expect(fn () => $manager->update(['supervisor_id' => $supervisor->getKey()]))
        ->toThrow(ValidationException::class);

    expect($manager->fresh()->supervisor_id)->toBeNull();
});

it('isDirectSupervisorOf recognizes direct reports', function () {
    $supervisor = makeHierarchyUser(SystemRole::SUPERVISOR->value);
    $employee = makeHierarchyUser(SystemRole::EMPLOYEE->value);
    $employee->update(['supervisor_id' => $supervisor->getKey()]);

    expect($supervisor->isDirectSupervisorOf($employee->fresh()))->toBeTrue()
        ->and($supervisor->manages($employee->fresh()))->toBeTrue();
});

it('canAssessDirectReports returns true for Manager and Supervisor', function () {
    $manager = makeHierarchyUser(SystemRole::MANAGER->value);
    $supervisor = makeHierarchyUser(SystemRole::SUPERVISOR->value);

    expect($manager->canAssessDirectReports())->toBeTrue()
        ->and($supervisor->canAssessDirectReports())->toBeTrue();
});

it('canAssessDirectReports returns false for Employee and Approver', function () {
    $employee = makeHierarchyUser(SystemRole::EMPLOYEE->value);
    $approver = makeHierarchyUser(SystemRole::APPROVER->value);

    expect($employee->canAssessDirectReports())->toBeFalse()
        ->and($approver->canAssessDirectReports())->toBeFalse();
});
