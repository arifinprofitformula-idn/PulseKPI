<?php

use App\Enums\SystemRole;
use App\Filament\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Resources\Divisions\Pages\CreateDivision;
use App\Filament\Resources\Positions\Pages\CreatePosition;
use App\Models\Department;
use App\Models\Division;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('hrd can create a division', function () {
    $user = User::factory()->create();
    $user->assignRole(SystemRole::HRD->value);

    $this->actingAs($user);

    Livewire::test(CreateDivision::class)
        ->fillForm([
            'name' => 'People & Culture',
            'code' => 'DIV-HRD',
            'description' => 'Handles employee governance.',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Division::class, [
        'name' => 'People & Culture',
        'code' => 'DIV-HRD',
    ]);
});

it('hrd can create a department under a division', function () {
    $user = User::factory()->create();
    $user->assignRole(SystemRole::HRD->value);
    $division = Division::factory()->create();

    $this->actingAs($user);

    Livewire::test(CreateDepartment::class)
        ->fillForm([
            'division_id' => $division->getKey(),
            'name' => 'Talent Management',
            'code' => 'DEP-TM',
            'description' => 'Runs staffing and performance cycles.',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Department::class, [
        'division_id' => $division->getKey(),
        'name' => 'Talent Management',
        'code' => 'DEP-TM',
    ]);
});

it('hrd can create a position under a department', function () {
    $user = User::factory()->create();
    $user->assignRole(SystemRole::HRD->value);
    $department = Department::factory()->create();

    $this->actingAs($user);

    Livewire::test(CreatePosition::class)
        ->fillForm([
            'department_id' => $department->getKey(),
            'name' => 'Senior Recruiter',
            'code' => 'POS-SR',
            'level' => 'Senior',
            'description' => 'Supports hiring operations.',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Position::class, [
        'department_id' => $department->getKey(),
        'name' => 'Senior Recruiter',
        'code' => 'POS-SR',
    ]);
});

it('employee cannot create organization records', function () {
    $user = User::factory()->create();
    $user->assignRole(SystemRole::EMPLOYEE->value);

    $this->actingAs($user)
        ->get('/admin/divisions/create')
        ->assertForbidden();

    $this->actingAs($user)
        ->get('/admin/departments/create')
        ->assertForbidden();

    $this->actingAs($user)
        ->get('/admin/positions/create')
        ->assertForbidden();
});

it('organization resources require authentication', function () {
    $this->get('/admin/divisions')->assertRedirect('/admin/login');
    $this->get('/admin/departments')->assertRedirect('/admin/login');
    $this->get('/admin/positions')->assertRedirect('/admin/login');
});
