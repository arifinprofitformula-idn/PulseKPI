# PulseKPI Execution Roadmap

## Phase 0 - Foundation Sprint

Goal: prepare the Laravel 12 baseline, authentication, admin access, quality tooling, CI, and delivery docs.

Deliverables:

- Laravel project initialized
- Authentication installed
- Filament admin panel installed
- Filament version aligned to `filament/filament ^5.6`
- Spatie permission installed
- Roles and permissions seeded
- Pest, Pint, and PHPStan configured
- GitHub Actions CI configured
- AGENTS.md and phase docs aligned
- Basic admin access tests passing

Exit criteria:

- `composer pint` passes
- `php artisan test` passes
- `./vendor/bin/phpstan analyse --memory-limit=1G` passes

## Phase 1 - Organization & Access

Deliverables:

- Division management
- Department management
- Position management
- User profile extension
- Supervisor relationships
- Policy matrix

## Phase 2 - KPI Template Engine

Deliverables:

- `KpiTemplate`
- `KpiTemplateItem`
- `KpiScoreRule`
- Template versioning
- Template preview

## Phase 3 - KPI Assignment

Deliverables:

- `KpiPeriod`
- `KpiAssignment`
- Manual assignment
- Bulk assignment
- Assignment notification

## Phase 4 - Assessment & Calculation

Deliverables:

- `KpiAssessment`
- `KpiAssessmentItem`
- Evidence notes and uploads
- Attendance adjustment
- KPI calculator service
- Assessment submission flow

## Phase 5 - Approval Workflow

Deliverables:

- HRD review
- Approver approve or reject
- Lock assessment
- Audit trail
- Workflow notifications

## Phase 6 - Dashboard & Reports

Deliverables:

- HRD dashboard
- Manager dashboard
- Employee dashboard
- Report filters
- Queued Excel export
- PDF export

## Phase 7 - Hardening & Go-Live

Deliverables:

- Security review
- Performance tuning
- UAT preparation
- Backup readiness
- Deployment checklist
