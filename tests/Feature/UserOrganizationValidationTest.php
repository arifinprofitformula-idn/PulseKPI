<?php

use App\Models\Department;
use App\Models\Division;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('rejects a user organization assignment when the department does not belong to the division', function () {
    $division = Division::factory()->create();
    $otherDivision = Division::factory()->create();
    $department = Department::factory()->create([
        'division_id' => $otherDivision->getKey(),
    ]);

    $user = User::factory()->create();

    try {
        $user->update([
            'division_id' => $division->getKey(),
            'department_id' => $department->getKey(),
        ]);

        $this->fail('Expected a validation exception to be thrown.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('department_id');
    }

    $user->refresh();

    expect($user->division_id)->toBeNull();
    expect($user->department_id)->toBeNull();
});

it('rejects a user organization assignment when the position does not belong to the department', function () {
    $division = Division::factory()->create();
    $department = Department::factory()->create([
        'division_id' => $division->getKey(),
    ]);
    $otherDepartment = Department::factory()->create();
    $position = Position::factory()->create([
        'department_id' => $otherDepartment->getKey(),
    ]);

    $user = User::factory()->create();

    try {
        $user->update([
            'division_id' => $division->getKey(),
            'department_id' => $department->getKey(),
            'position_id' => $position->getKey(),
        ]);

        $this->fail('Expected a validation exception to be thrown.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('position_id');
    }

    $user->refresh();

    expect($user->division_id)->toBeNull();
    expect($user->department_id)->toBeNull();
    expect($user->position_id)->toBeNull();
});

it('rejects a user assigning themselves as their own supervisor', function () {
    $user = User::factory()->create();

    try {
        $user->update([
            'supervisor_id' => $user->getKey(),
        ]);

        $this->fail('Expected a validation exception to be thrown.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('supervisor_id');
    }

    $user->refresh();

    expect($user->supervisor_id)->toBeNull();
});
