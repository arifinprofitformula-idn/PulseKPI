<?php

use App\Enums\SystemRole;
use App\Models\Department;
use App\Models\Division;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('manager can view a direct subordinate profile', function () {
    $manager = User::factory()->create();
    $manager->assignRole(SystemRole::MANAGER->value);

    $subordinate = User::factory()->create([
        'supervisor_id' => $manager->getKey(),
    ]);

    $this->actingAs($manager)
        ->get(route('users.show', $subordinate))
        ->assertOk()
        ->assertSee($subordinate->name);
});

it('manager cannot view an unrelated employee profile', function () {
    $manager = User::factory()->create();
    $manager->assignRole(SystemRole::MANAGER->value);

    $employee = User::factory()->create();

    $this->actingAs($manager)
        ->get(route('users.show', $employee))
        ->assertForbidden();
});

it('employee can view their own profile', function () {
    $employee = User::factory()->create();
    $employee->assignRole(SystemRole::EMPLOYEE->value);

    $this->actingAs($employee)
        ->get(route('users.show', $employee))
        ->assertOk()
        ->assertSee($employee->email);
});

it('employee cannot view another unrelated employee profile', function () {
    $employee = User::factory()->create();
    $employee->assignRole(SystemRole::EMPLOYEE->value);
    $otherEmployee = User::factory()->create();
    $otherEmployee->assignRole(SystemRole::EMPLOYEE->value);

    $this->actingAs($employee)
        ->get(route('users.show', $otherEmployee))
        ->assertForbidden();
});

it('user organization relationships work correctly', function () {
    $division = Division::factory()->create();
    $department = Department::factory()->create([
        'division_id' => $division->getKey(),
    ]);
    $position = Position::factory()->create([
        'department_id' => $department->getKey(),
    ]);
    $supervisor = User::factory()->create();
    $supervisor->assignRole(SystemRole::SUPERVISOR->value);
    $subordinate = User::factory()->create([
        'division_id' => $division->getKey(),
        'department_id' => $department->getKey(),
        'position_id' => $position->getKey(),
        'supervisor_id' => $supervisor->getKey(),
    ]);

    expect($subordinate->division)->id->toBe($division->id);
    expect($subordinate->department)->id->toBe($department->id);
    expect($subordinate->position)->id->toBe($position->id);
    expect($subordinate->supervisor)->id->toBe($supervisor->id);
    expect($supervisor->subordinates->pluck('id')->all())->toContain($subordinate->id);
});
