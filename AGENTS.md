# PulseKPI AI Agent Rules

## Project Context

This is a Laravel 12 KPI Management Platform for HRD, Managers, Employees, and Directors.

The platform replaces Excel-based KPI submission with structured KPI templates, KPI assignment, scoring, approval workflow, dashboard, reporting, export, and audit trail.

## Product Name

Default product name: PulseKPI.

If the project name changes, update documentation and UI labels consistently, but do not rename namespaces or folders unless explicitly instructed.

## Tech Stack

- Laravel 12
- PHP 8.3+
- PostgreSQL 15+ or MySQL 8
- Redis
- Filament 5.6
- Livewire 3
- Laravel Queue
- Laravel Horizon
- Pest
- Laravel Pint
- Larastan / PHPStan
- Vite

## Architecture

Use modular monolith.

Business logic must be placed in:

- app/Actions
- app/Services

Do not place business logic in:

- Controllers
- Blade views
- Filament pages
- Form Requests
- Models, unless it is simple relationship, cast, scope, or accessor logic

## Required Laravel Patterns

Use:

- Form Request for validation
- Policy/Gate for authorization
- Enum for status/state
- Service or Action class for business process
- API Resource for JSON response
- Queue Job for heavy process
- Event/Listener for notification and audit log
- Factory and Seeder for testable setup data

## Security Rules

- Validate all user input using Form Requests.
- Authorize every sensitive action using Policy/Gate.
- Never hardcode secrets.
- Never expose private files publicly.
- Approved or locked KPI cannot be edited without explicit permission.
- Add audit log for create, update, submit, review, approve, reject, lock, and export.
- Use private storage for KPI evidence files.
- Do not use raw SQL unless bindings are used and the reason is documented.
- Prevent mass assignment issues by defining fillable fields.

## Performance Rules

- Use eager loading to prevent N+1 queries.
- Use pagination for list pages.
- Add database indexes for filtered columns.
- Cache dashboard queries.
- Queue exports and long-running jobs.
- Use chunk, lazy, or cursor for large datasets.
- Avoid get()->map() for report-scale queries; prefer aggregate queries.

## Testing Rules

Every non-trivial feature must include Pest tests:

- guest access test
- authorization test
- validation test
- happy path test
- calculation test if applicable
- forbidden path test for locked or approved data

## Database Rules

- Use migrations for schema changes.
- Use foreign keys.
- Use decimal for KPI weights and scores.
- Never use float for score or financial values.
- Add indexes for common filters: year, month, status, employee_id, assessor_id, division_id, department_id, position_id.
- Migrations must be rollback-safe.

## Git Rules

Use feature branches:

- feature/kpi-template-management
- feature/kpi-assignment
- feature/kpi-assessment
- feature/kpi-approval-workflow
- feature/kpi-dashboard
- feature/kpi-export

Never commit directly to main.

## Before Finishing Any Task

Run:

```bash
composer pint
php artisan test
./vendor/bin/phpstan analyse --memory-limit=1G
```

If frontend assets changed, also run:

```bash
npm run build
```

## Agent Output Expectation

After implementation, summarize:

- changed files
- migrations added
- tests added
- security checks applied
- performance considerations
- commands executed
- remaining risks or TODOs

Do not modify unrelated files.
