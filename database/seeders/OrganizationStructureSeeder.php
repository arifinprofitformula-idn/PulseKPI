<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Division;
use App\Models\Position;
use Illuminate\Database\Seeder;

class OrganizationStructureSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = [
            'CORP' => [
                'name' => 'Corporate Services',
                'description' => 'Shared support functions for the business.',
                'departments' => [
                    'HRD' => [
                        'name' => 'Human Resources',
                        'description' => 'People operations and performance administration.',
                        'positions' => [
                            'HRD-MGR' => ['name' => 'HR Manager', 'level' => 'Manager'],
                            'HRD-STAFF' => ['name' => 'HR Officer', 'level' => 'Staff'],
                        ],
                    ],
                ],
            ],
            'OPS' => [
                'name' => 'Operations',
                'description' => 'Day-to-day business execution teams.',
                'departments' => [
                    'OPS-GEN' => [
                        'name' => 'General Operations',
                        'description' => 'Operational delivery and supervision.',
                        'positions' => [
                            'OPS-MGR' => ['name' => 'Operations Manager', 'level' => 'Manager'],
                            'OPS-LEAD' => ['name' => 'Operations Lead', 'level' => 'Lead'],
                            'OPS-STAFF' => ['name' => 'Operations Staff', 'level' => 'Staff'],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($divisions as $divisionCode => $divisionData) {
            $division = Division::query()->updateOrCreate(
                ['code' => $divisionCode],
                [
                    'name' => $divisionData['name'],
                    'description' => $divisionData['description'],
                    'is_active' => true,
                ],
            );

            foreach ($divisionData['departments'] as $departmentCode => $departmentData) {
                $department = Department::query()->updateOrCreate(
                    ['code' => $departmentCode],
                    [
                        'division_id' => $division->getKey(),
                        'name' => $departmentData['name'],
                        'description' => $departmentData['description'],
                        'is_active' => true,
                    ],
                );

                foreach ($departmentData['positions'] as $positionCode => $positionData) {
                    Position::query()->updateOrCreate(
                        ['code' => $positionCode],
                        [
                            'department_id' => $department->getKey(),
                            'name' => $positionData['name'],
                            'level' => $positionData['level'],
                            'description' => null,
                            'is_active' => true,
                        ],
                    );
                }
            }
        }
    }
}
