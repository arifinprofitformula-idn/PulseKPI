<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use App\Models\Department;
use App\Models\Division;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $corpDivision = Division::query()->where('code', 'CORP')->first();
        $opsDivision = Division::query()->where('code', 'OPS')->first();

        $hrdDepartment = Department::query()->where('code', 'HRD')->first();
        $opsDepartment = Department::query()->where('code', 'OPS-GEN')->first();

        $hrdManagerPosition = Position::query()->where('code', 'HRD-MGR')->first();
        $hrdStaffPosition = Position::query()->where('code', 'HRD-STAFF')->first();
        $opsManagerPosition = Position::query()->where('code', 'OPS-MGR')->first();
        $opsStaffPosition = Position::query()->where('code', 'OPS-STAFF')->first();

        $accounts = [
            [
                'role' => SystemRole::HRD,
                'name' => 'Demo HRD',
                'email' => 'hrd@demo.test',
                'employee_code' => 'EMP-HRD-001',
                'division_id' => $corpDivision?->getKey(),
                'department_id' => $hrdDepartment?->getKey(),
                'position_id' => $hrdStaffPosition?->getKey(),
                'employment_status' => 'active',
                'joined_at' => '2023-01-01',
            ],
            [
                'role' => SystemRole::MANAGER,
                'name' => 'Demo Manager',
                'email' => 'manager@demo.test',
                'employee_code' => 'EMP-MGR-001',
                'division_id' => $opsDivision?->getKey(),
                'department_id' => $opsDepartment?->getKey(),
                'position_id' => $opsManagerPosition?->getKey(),
                'employment_status' => 'active',
                'joined_at' => '2022-06-01',
            ],
            [
                'role' => SystemRole::EMPLOYEE,
                'name' => 'Demo Employee',
                'email' => 'employee@demo.test',
                'employee_code' => 'EMP-STF-001',
                'division_id' => $opsDivision?->getKey(),
                'department_id' => $opsDepartment?->getKey(),
                'position_id' => $opsStaffPosition?->getKey(),
                'employment_status' => 'active',
                'joined_at' => '2023-03-01',
            ],
            [
                'role' => SystemRole::APPROVER,
                'name' => 'Demo Approver',
                'email' => 'approver@demo.test',
                'employee_code' => 'EMP-APR-001',
                'division_id' => $corpDivision?->getKey(),
                'department_id' => $hrdDepartment?->getKey(),
                'position_id' => $hrdManagerPosition?->getKey(),
                'employment_status' => 'active',
                'joined_at' => '2021-01-01',
            ],
        ];

        $manager = null;

        foreach ($accounts as $account) {
            $role = $account['role'];

            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'employee_code' => $account['employee_code'],
                    'division_id' => $account['division_id'],
                    'department_id' => $account['department_id'],
                    'position_id' => $account['position_id'],
                    'employment_status' => $account['employment_status'],
                    'joined_at' => $account['joined_at'],
                ],
            );

            $user->syncRoles([$role->value]);

            if ($role === SystemRole::MANAGER) {
                $manager = $user;
            }

            if ($role === SystemRole::EMPLOYEE && $manager !== null) {
                $user->update(['supervisor_id' => $manager->getKey()]);
            }
        }

        $this->command->info('Demo users created:');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Super Admin', 'lihat config pulsekpi.super_admin', '(dari config)'],
                ['HRD', 'hrd@demo.test', 'password'],
                ['Manager', 'manager@demo.test', 'password'],
                ['Employee', 'employee@demo.test', 'password'],
                ['Approver', 'approver@demo.test', 'password'],
            ],
        );
    }
}
