<?php

namespace App\Enums;

enum SystemPermission: string
{
    case ACCESS_ADMIN_PANEL = 'access admin panel';
    case MANAGE_USERS = 'manage users';
    case MANAGE_ORGANIZATION = 'manage organization';
    case MANAGE_KPI_TEMPLATES = 'manage kpi templates';
    case ASSIGN_KPI = 'assign kpi';
    case SUBMIT_KPI_ASSESSMENT = 'submit kpi assessment';
    case REVIEW_KPI_ASSESSMENT = 'review kpi assessment';
    case APPROVE_KPI_ASSESSMENT = 'approve kpi assessment';
    case VIEW_REPORTS = 'view reports';
    case EXPORT_REPORTS = 'export reports';
}
