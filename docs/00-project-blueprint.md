# PulseKPI Project Blueprint

## Product Vision

PulseKPI is a Laravel 12 KPI Management Platform for HRD, Managers, Employees, and Approvers.
It replaces Excel-driven KPI administration with a structured workflow for templates, assignments,
assessment, approval, reporting, exports, and auditability.

## Primary Users

1. Super Admin
2. HRD
3. Manager
4. Employee
5. Approver

## Core Business Flow

1. HRD defines KPI templates and score rules.
2. HRD assigns KPI templates to employees by period.
3. Employees and managers submit KPI assessment data and evidence.
4. The system calculates KPI results consistently.
5. HRD reviews submissions before approval.
6. Approvers approve or reject final KPI outcomes.
7. Approved assessments are locked and auditable.
8. HRD exports reports and leadership dashboards.

## Architecture Guardrails

- Use a modular monolith structure.
- Put business logic in `app/Actions` or `app/Services`.
- Keep controllers, Blade views, and Filament pages thin.
- Use Form Requests, Policies/Gates, Queue Jobs, Events/Listeners, and API Resources where applicable.
- Use enums for statuses and state transitions.

## Foundation Stack

- Laravel 12
- Filament `^5.6`
- Laravel Breeze for authentication
- Spatie Laravel Permission for access control
- Pest, Pint, and PHPStan for quality gates

## MVP Modules

1. Foundation and access control
2. Organization structure
3. KPI template engine
4. KPI assignment
5. KPI assessment and calculation
6. Approval workflow
7. Dashboard and reports
8. Export and audit trail

## Non-Functional Requirements

- Protect all sensitive actions with authorization.
- Store private evidence files outside the public web root.
- Avoid N+1 queries and paginate list views.
- Queue long-running exports and notifications.
- Add automated tests for authorization, validation, happy path, and locked-data restrictions.
