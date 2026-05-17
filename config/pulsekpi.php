<?php

use App\Enums\SystemPermission;
use App\Enums\SystemRole;

return [
    'roles' => array_map(
        static fn (SystemRole $role): string => $role->value,
        SystemRole::cases(),
    ),

    'permissions' => array_map(
        static fn (SystemPermission $permission): string => $permission->value,
        SystemPermission::cases(),
    ),

    'super_admin' => [
        'name' => env('SUPER_ADMIN_NAME', 'PulseKPI Super Admin'),
        'email' => env('SUPER_ADMIN_EMAIL', 'admin@pulsekpi.test'),
        'password' => env('SUPER_ADMIN_PASSWORD', 'ChangeMe123!'),
    ],

    'assessments' => [
        'evidence_disk' => env('KPI_ASSESSMENT_EVIDENCE_DISK', env('FILESYSTEM_DISK', 'local')),
        'max_evidence_size_kb' => (int) env('KPI_ASSESSMENT_EVIDENCE_MAX_KB', 5120),
    ],

    'exports' => [
        'disk' => env('KPI_REPORT_EXPORT_DISK', env('FILESYSTEM_DISK', 'local')),
    ],
];
