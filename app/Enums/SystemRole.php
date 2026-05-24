<?php

namespace App\Enums;

enum SystemRole: string
{
    case SUPER_ADMIN = 'Super Admin';
    case HRD = 'HRD';
    case MANAGER = 'Manager';
    case SUPERVISOR = 'Supervisor';
    case EMPLOYEE = 'Employee';
    case APPROVER = 'Approver';
}
