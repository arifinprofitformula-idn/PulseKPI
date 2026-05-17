<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = config('pulsekpi.super_admin');

        $user = User::query()->updateOrCreate(
            ['email' => $superAdmin['email']],
            [
                'name' => $superAdmin['name'],
                'password' => Hash::make($superAdmin['password']),
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles([SystemRole::SUPER_ADMIN->value]);
    }
}
