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

it('allows a super admin to access the admin panel', function () {
    $user = User::factory()->create();
    $user->assignRole(SystemRole::SUPER_ADMIN->value);

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk();
});

it('allows a user with the panel permission to access the admin panel', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(SystemPermission::ACCESS_ADMIN_PANEL->value);

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk();
});

it('forbids a user without the panel permission from accessing the admin panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});
