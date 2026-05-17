<?php

use App\Enums\SystemRole;
use App\Filament\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Resources\Departments\Pages\EditDepartment;
use App\Filament\Resources\Divisions\Pages\CreateDivision;
use App\Filament\Resources\Divisions\Pages\EditDivision;
use App\Filament\Resources\Positions\Pages\CreatePosition;
use App\Filament\Resources\Positions\Pages\EditPosition;
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

it('hrd can update a division', function () {
    $user = User::factory()->create();
    $user->assignRole(SystemRole::HRD->value);
    $division = Division::factory()->create();

    $this->actingAs($user);

    Livewire::test(EditDivision::class, ['record' => $division->getRouteKey()])
        ->fillForm([
            'name' => 'Corporate Services',
            'code' => 'DIV-CS',
            'description' => 'Shared services and support.',
            'is_active' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Division::class, [
        'id' => $division->getKey(),
        'name' => 'Corporate Services',
        'code' => 'DIV-CS',
        'is_active' => false,
    ]);
});

it('hrd can delete a division without dependent departments', function () {
    $user = User::factory()->create();
    $user->assignRole(SystemRole::HRD->value);
    $division = Division::factory()->create();

    $this->actingAs($user);

    Livewire::test(EditDivision::class, ['record' => $division->getRouteKey()])
        ->callAction('delete');

    $this->assertModelMissing($division);
});

it('hrd can update a department', function () {
    $user = User::factory()->create();
    $user->assignRole(SystemRole::HRD->value);
    $division = Division::factory()->create();
    $department = Department::factory()->create();

    $this->actingAs($user);

    Livewire::test(EditDepartment::class, ['record' => $department->getRouteKey()])
        ->fillForm([
            'division_id' => $division->getKey(),
            'name' => 'Business Partners',
            'code' => 'DEP-BP',
            'description' => 'Partners with business units.',
            'is_active' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Department::class, [
        'id' => $department->getKey(),
        'division_id' => $division->getKey(),
        'name' => 'Business Partners',
        'code' => 'DEP-BP',
        'is_active' => false,
    ]);
});

it('hrd can delete a department without dependent positions', function () {
    $user = User::factory()->create();
    $user->assignRole(SystemRole::HRD->value);
    $department = Department::factory()->create();

    $this->actingAs($user);

    Livewire::test(EditDepartment::class, ['record' => $department->getRouteKey()])
        ->callAction('delete');

    $this->assertModelMissing($department);
});

it('hrd can update a position', function () {
    $user = User::factory()->create();
    $user->assignRole(SystemRole::HRD->value);
    $department = Department::factory()->create();
    $position = Position::factory()->create();

    $this->actingAs($user);

    Livewire::test(EditPosition::class, ['record' => $position->getRouteKey()])
        ->fillForm([
            'department_id' => $department->getKey(),
            'name' => 'Lead Analyst',
            'code' => 'POS-LA',
            'level' => 'Lead',
            'description' => 'Oversees analysis delivery.',
            'is_active' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Position::class, [
        'id' => $position->getKey(),
        'department_id' => $department->getKey(),
        'name' => 'Lead Analyst',
        'code' => 'POS-LA',
        'level' => 'Lead',
        'is_active' => false,
    ]);
});

it('hrd can delete a position', function () {
    $user = User::factory()->create();
    $user->assignRole(SystemRole::HRD->value);
    $position = Position::factory()->create();

    $this->actingAs($user);

    Livewire::test(EditPosition::class, ['record' => $position->getRouteKey()])
        ->callAction('delete');

    $this->assertModelMissing($position);
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

it('employee cannot access organization list and edit pages', function () {
    $user = User::factory()->create();
    $user->assignRole(SystemRole::EMPLOYEE->value);
    $division = Division::factory()->create();
    $department = Department::factory()->create();
    $position = Position::factory()->create();

    $this->actingAs($user)
        ->get('/admin/divisions')
        ->assertForbidden();

    $this->actingAs($user)
        ->get("/admin/divisions/{$division->getRouteKey()}/edit")
        ->assertForbidden();

    $this->actingAs($user)
        ->get('/admin/departments')
        ->assertForbidden();

    $this->actingAs($user)
        ->get("/admin/departments/{$department->getRouteKey()}/edit")
        ->assertForbidden();

    $this->actingAs($user)
        ->get('/admin/positions')
        ->assertForbidden();

    $this->actingAs($user)
        ->get("/admin/positions/{$position->getRouteKey()}/edit")
        ->assertForbidden();
});

it('organization resources require authentication', function () {
    $this->get('/admin/divisions')->assertRedirect('/admin/login');
    $this->get('/admin/divisions/1/edit')->assertRedirect('/admin/login');
    $this->get('/admin/departments')->assertRedirect('/admin/login');
    $this->get('/admin/departments/1/edit')->assertRedirect('/admin/login');
    $this->get('/admin/positions')->assertRedirect('/admin/login');
    $this->get('/admin/positions/1/edit')->assertRedirect('/admin/login');
});
