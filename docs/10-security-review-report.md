# PulseKPI Security Review Report

**Review date:** <!-- YYYY-MM-DD -->  
**Reviewer:** <!-- name / team -->  
**Build reviewed:** <!-- git tag or commit SHA -->  
**Scope:** Phase 7 hardening — full codebase security review against `docs/04-security-checklist.md`  

---

## Summary

PulseKPI Phase 7 hardening was reviewed against the project security checklist.
Two issues found during review were fixed before this report was finalised:

1. **Super Admin could not download evidence files** — `KpiAssessmentPolicy::downloadEvidence()` did not include the Super Admin role. Fixed in this phase.
2. **Assignment with existing assessment could be cancelled** — `CancelKpiAssignmentAction` allowed cancellation even when an assessment existed for the assignment. Fixed in this phase.

All automated quality gates pass (179 tests, PHPStan no errors, npm build success).
No known critical or high risks remain. See "Remaining Risks / Accepted Limitations" at the bottom of this report.

---

## Review Findings Table

### Authentication

| # | Check | Result | Evidence | Notes |
|---|-------|--------|---------|-------|
| AU-01 | Login is required for all KPI pages | Pass | All admin panel routes use Filament's `Authenticate` auth middleware (`AdminPanelProvider`). Web routes in `routes/web.php` use `middleware('auth')` group. | |
| AU-02 | Rate limiting is applied to login | Pass | Laravel Breeze's `LoginRequest` applies rate limiting via `RateLimiter` — 5 attempts per minute per IP + email. | |
| AU-03 | Password hashing uses bcrypt with appropriate rounds | Pass | `BCRYPT_ROUNDS=12` in `.env.example`. `config/hashing.php` defaults to bcrypt. | |
| AU-04 | Email verification is available | Pass | `routes/auth.php` includes signed email verification routes. | |
| AU-05 | CSRF protection is active | Pass | `VerifyCsrfToken` is included in the Filament middleware stack (`AdminPanelProvider`). All POST/PATCH/DELETE forms are CSRF-protected. | |

---

### Authorization

| # | Check | Result | Evidence | Notes |
|---|-------|--------|---------|-------|
| AZ-01 | Policy/Gate is used for every sensitive action | Pass | 11 policies registered covering KpiAssessment, KpiAssignment, KpiTemplate, KpiTemplateItem, KpiScoreRule, KpiPeriod, KpiReportExport, Division, Department, Position, User. | |
| AZ-02 | All workflow actions (submit, review, approve, reject, lock) use Gate | Pass | `LockKpiAssessmentAction`, `ReviewKpiAssessmentAction`, `ApproveKpiAssessmentAction`, `RejectKpiAssessmentAction`, `SubmitKpiAssessmentAction` all call `Gate::forUser($actor)->authorize(...)`. | |
| AZ-03 | Export and PDF export use Gate | Pass | `KpiAssessmentDetailPdfExportAction` and `RequestKpiAssessmentReportExportAction` both authorize via Gate. `KpiReportExportDownloadController` calls `Gate::authorize('download', ...)`. | |
| AZ-04 | Evidence download uses Gate | Pass | `KpiAssessmentEvidenceDownloadController` calls `Gate::authorize('downloadEvidence', $item->assessment)`. | |
| AZ-05 | Super Admin has evidence download access | Pass | **Fixed in Phase 7.** `KpiAssessmentPolicy::downloadEvidence()` now includes Super Admin role check. | |
| AZ-06 | Filament resources scope their Eloquent queries | Pass | `KpiAssessmentResource::getEloquentQuery()` applies `visibleToUser($user)`. `KpiAssessmentReportResource::getEloquentQuery()` uses `BuildKpiAssessmentReportQuery`. `KpiReportExportResource::getEloquentQuery()` filters by `requested_by` for non-admin roles. | |
| AZ-07 | Filament panel requires authentication | Pass | `Authenticate::class` is listed in `authMiddleware` in `AdminPanelProvider`. | |

---

### Role Visibility

| # | Check | Result | Evidence | Notes |
|---|-------|--------|---------|-------|
| RV-01 | Employee can only see own assessments | Pass | `KpiAssessment::scopeVisibleToUser()` returns `where('employee_id', $user->getKey())` for non-manager employees. Covered by `KpiDashboardReportTest: employee report only returns own rows`. | |
| RV-02 | Manager can only see direct subordinate assessments | Pass | `visibleToUser` uses `whereHas('employee', fn => where('supervisor_id', $user->getKey()))`. Covered by `KpiDashboardReportTest: manager report only returns direct subordinate rows`. | |
| RV-03 | Approver can only see REVIEWED/APPROVED/LOCKED assessments | Pass | `visibleToUser` filters by `whereIn('status', [REVIEWED, APPROVED, LOCKED])` for Approver. Covered by `KpiDashboardReportTest: approver report only returns reviewed approved and locked rows`. | |
| RV-04 | HRD sees all assessments | Pass | `visibleToUser` returns unfiltered query for HRD. | |
| RV-05 | Dashboard cache keys are user-scoped | Pass | `KpiDashboardService::makeCacheKey()` includes user ID. Covered by `KpiDashboardReportTest: dashboard cache key includes user id and scope context`. | |
| RV-06 | Dashboard cache does not bleed between users | Pass | Cache key uniqueness verified by test. Different user IDs and different filter sets produce different keys. | |

---

### KPI Template Locking

| # | Check | Result | Evidence | Notes |
|---|-------|--------|---------|-------|
| TL-01 | Published template cannot be edited | Pass | `KpiTemplatePolicy::update()` returns `false` when `$template->published_at !== null`. `KpiTemplateItemPolicy::update()` delegates to `KpiTemplatePolicy::update()`. | |
| TL-02 | Published template cannot be deleted | Pass | `KpiTemplatePolicy::delete()` checks `published_at === null`. | |
| TL-03 | Published template cannot be further published | Pass | `KpiTemplatePolicy::publish()` checks `published_at === null`. | |
| TL-04 | Template item weights validated at publish | Pass | `PublishKpiTemplateAction` calls `ValidateKpiTemplateWeightAction::ensureEqualsOneHundred()` before setting `published_at`. | |
| TL-05 | Publish uses a database transaction | Pass | `PublishKpiTemplateAction::execute()` wraps the save and audit log in `DB::transaction()`. | |

---

### Assignment Integrity

| # | Check | Result | Evidence | Notes |
|---|-------|--------|---------|-------|
| AI-01 | Duplicate assignment for same employee+period is rejected | Pass | `AssignKpiTemplateAction` checks for existing assignment before creating. Covered by `KpiAssignmentManagementTest: duplicate assignment for same period and employee is rejected`. | |
| AI-02 | Cannot assign a draft (unpublished) template | Pass | `AssignKpiTemplateAction` throws `ValidationException` if `published_at` is null. | |
| AI-03 | Cannot assign to an inactive period | Pass | `AssignKpiTemplateAction` validates `period->is_active`. | |
| AI-04 | Cannot cancel an assignment that has an existing assessment | Pass | **Fixed in Phase 7.** `CancelKpiAssignmentAction` now calls `$assignment->assessment()->exists()` and throws `ValidationException` if true. Covered by three new tests in `KpiAssignmentManagementTest`. | |
| AI-05 | Double-cancel is rejected | Pass | `CancelKpiAssignmentAction` throws `ValidationException` if already CANCELLED. | |

---

### Assessment Submit Validation

| # | Check | Result | Evidence | Notes |
|---|-------|--------|---------|-------|
| SV-01 | All required items must be scored before submit | Pass | `SubmitKpiAssessmentAction` validates that all items with `template_item_is_required = true` have a non-null score. | |
| SV-02 | Score value must be 0, 1, or 2 | Pass | `UpdateKpiAssessmentItemAction` validates `score` as `in:0,1,2`. | |
| SV-03 | Evidence file validated by mime type, extension, and size | Pass | `UpdateKpiAssessmentItemAction` validates `mimes:pdf,jpg,jpeg,png` and `max:5120` (5 MB). | |
| SV-04 | Cannot submit an already-submitted assessment | Pass | `SubmitKpiAssessmentAction` checks `isEditable()` (DRAFT or REJECTED) before proceeding. | |
| SV-05 | Score calculation uses integer arithmetic to avoid floating-point drift | Pass | `CalculateKpiAssessmentScoreAction` converts all values to integer hundredths before arithmetic. | |

---

### Approval / Lock Integrity

| # | Check | Result | Evidence | Notes |
|---|-------|--------|---------|-------|
| AL-01 | Only HRD can review (SUBMITTED → REVIEWED) | Pass | `KpiAssessmentPolicy::review()` allows only SUPER_ADMIN and HRD. | |
| AL-02 | Only Approver can approve (REVIEWED → APPROVED) | Pass | `KpiAssessmentPolicy::approve()` allows only SUPER_ADMIN and APPROVER. | |
| AL-03 | HRD can only reject SUBMITTED; Approver can only reject REVIEWED | Pass | `KpiAssessmentPolicy::reject()` checks both role and current status. | |
| AL-04 | Only Approver can lock (APPROVED → LOCKED) | Pass | `KpiAssessmentPolicy::lock()` allows only SUPER_ADMIN and APPROVER. | |
| AL-05 | Workflow transitions use database transactions | Pass | `ReviewKpiAssessmentAction`, `ApproveKpiAssessmentAction`, `RejectKpiAssessmentAction`, `LockKpiAssessmentAction` all wrap status change and transition record in `DB::transaction()`. | |
| AL-06 | Locked assessment cannot be edited | Pass | `KpiAssessment::isEditable()` returns `true` only for DRAFT and REJECTED. LOCKED is excluded. `KpiAssessmentPolicy::update()` checks `isEditable()`. | |
| AL-07 | `CalculateKpiAssessmentScoreAction` guards non-editable assessments | Pass | First statement: `if (!$assessment->isEditable()) return $assessment;` prevents score recalculation on submitted/approved/locked data. | |
| AL-08 | Audit trail is recorded for all workflow transitions | Pass | `RecordKpiAssessmentTransitionService` creates `KpiApproval` records. `ActivityLogService` logs events for submit, review, approve, reject, lock. | |

---

### Evidence Private Storage

| # | Check | Result | Evidence | Notes |
|---|-------|--------|---------|-------|
| EP-01 | Evidence files stored on private disk | Pass | `config('pulsekpi.assessments.evidence_disk')` defaults to `local` which maps to `storage/app/private`. | |
| EP-02 | Evidence not served through a public URL | Pass | No route serves evidence from the `public` disk. Evidence URL goes through `KpiAssessmentEvidenceDownloadController` which authorizes before streaming. | |
| EP-03 | Evidence file path not included in PDF exports | Pass | `resources/views/exports/kpi-assessment-detail.blade.php` does not render `evidence_file_path`. Covered by `KpiReportExportTest: assessment detail pdf view uses snapshot item fields and includes approval history without evidence file paths`. | |
| EP-04 | Evidence access authorized for HRD, assessor, employee, Super Admin | Pass | `KpiAssessmentPolicy::downloadEvidence()` — all four roles confirmed. Super Admin added in Phase 7. | |
| EP-05 | Unrelated users cannot access evidence | Pass | Covered by `KpiAssessmentManagementTest: unrelated employee cannot download another employee evidence`. | |
| EP-06 | Old evidence file deleted when replaced | Pass | `UpdateKpiAssessmentItemAction` deletes the old file from the disk before storing the new one. Covered by `KpiAssessmentManagementTest: replacing evidence deletes the old private file`. | |

---

### Export Private Storage

| # | Check | Result | Evidence | Notes |
|---|-------|--------|---------|-------|
| XP-01 | Export files stored on private disk | Pass | `config('pulsekpi.exports.disk')` defaults to `local`. `KpiReportExport` stores disk name alongside file path. | |
| XP-02 | Export download authorized | Pass | `KpiReportExportDownloadController` calls `Gate::authorize('download', $kpiReportExport)`. `KpiReportExportPolicy::download()` checks ownership and COMPLETED status. | |
| XP-03 | PENDING or FAILED exports cannot be downloaded | Pass | `KpiReportExportPolicy::download()` returns false unless `status === COMPLETED`. Covered by `KpiReportExportTest: pending or failed exports cannot be downloaded`. | |
| XP-04 | Other users cannot download another user's export | Pass | `KpiReportExportPolicy::view()` restricts by `requested_by` for non-HRD/non-SA users. Covered by `KpiReportExportTest: unauthorized user cannot download another users export`. | |
| XP-05 | Missing export file returns 404, not 500 | Pass | Controller checks `Storage::disk(...)->exists(...)` before serving. Covered by `KpiReportExportTest: missing export file returns a safe 404 response`. | |
| XP-06 | Export download is audited | Pass | `KpiReportExportDownloadController` calls `ActivityLogService::log('kpi_report_export.downloaded', ...)`. Covered by `KpiReportExportTest: owner can download a completed export and download is audited`. | |

---

### Dashboard / Report / Export Scoping

| # | Check | Result | Evidence | Notes |
|---|-------|--------|---------|-------|
| DS-01 | `BuildKpiAssessmentReportQuery` applies `visibleToUser` scope | Pass | `BuildKpiAssessmentReportQuery::execute()` calls `->visibleToUser($user)` as the base query. | |
| DS-02 | Dashboard controller scopes results to the current user | Pass | `MyKpiDashboardController` passes `$request->user()` to `BuildKpiAssessmentReportQuery`. | |
| DS-03 | Filament report resource uses scoped query | Pass | `KpiAssessmentReportResource::getEloquentQuery()` uses `BuildKpiAssessmentReportQuery`. | |
| DS-04 | KPI Assessment list in admin is scoped | Pass | `KpiAssessmentResource::getEloquentQuery()` calls `->visibleToUser($user)`. | |
| DS-05 | Scope isolation verified by automated tests | Pass | `KpiDashboardReportTest` contains 14+ tests covering all role scope boundaries. | |

---

### CSRF / Session / Cookie Configuration

| # | Check | Result | Evidence | Notes |
|---|-------|--------|---------|-------|
| CS-01 | CSRF protection active for all forms | Pass | `VerifyCsrfToken` middleware in Filament stack. Web routes benefit from Laravel's default CSRF handling. | |
| CS-02 | `SESSION_SECURE_COOKIE` documented for production | Pass | Added to `.env.example` in Phase 7 with production note. Included in `docs/07-production-checklist.md` item 4.1. | |
| CS-03 | `SESSION_ENCRYPT` documented for production | Pass | Added to `.env.example` in Phase 7 with production note. Included in checklist item 4.2. | |
| CS-04 | `APP_DEBUG=false` requirement documented | Pass | `.env.example` has inline comment. Checklist item 1.2. | |
| CS-05 | Cookies encrypted by Filament middleware | Pass | `EncryptCookies` middleware is in the Filament panel middleware stack. | |

---

### Queue / Export Job Security

| # | Check | Result | Evidence | Notes |
|---|-------|--------|---------|-------|
| QJ-01 | Report export job runs as the requesting user's context | Pass | `ExportKpiAssessmentReportJob` stores `requested_by` on the `KpiReportExport` record and uses it to scope the query via `BuildKpiAssessmentReportQuery`. | |
| QJ-02 | Export job handles failure gracefully | Pass | Job catches exceptions, marks export as FAILED, logs the error, and sends a failure notification. Covered by `KpiReportExportTest: failed excel export job marks the export as failed`. | |
| QJ-03 | Export job completion audited | Pass | `ActivityLogService::log('kpi_report_export.completed', ...)` is called on success. | |
| QJ-04 | Queue worker configured with retry limit | Pass | `config/queue.php` sets `retry_after: 90` and `.env.example` uses `QUEUE_CONNECTION=redis`. Supervisor config in `docs/08-deployment-notes.md` sets `--tries=3`. | |

---

### Secrets / Environment Management

| # | Check | Result | Evidence | Notes |
|---|-------|--------|---------|-------|
| SE-01 | No secrets hardcoded in source code | Pass | All credentials are accessed via `env()` or `config()`. No secrets found in `app/`, `config/`, or `routes/`. | |
| SE-02 | `.env` is in `.gitignore` | Pass | Standard Laravel `.gitignore` excludes `.env`. | |
| SE-03 | `SUPER_ADMIN_PASSWORD` fallback removed | Pass | **Fixed in Phase 7.** `config/pulsekpi.php` now uses `env('SUPER_ADMIN_PASSWORD')` with no fallback. If unset, `Hash::make(null)` will throw a `TypeError` rather than silently creating an account with a known password. | |
| SE-04 | AWS keys are blank in `.env.example` | Pass | `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY` are empty strings in the example file. | |

---

### Demo Seeders

| # | Check | Result | Evidence | Notes |
|---|-------|--------|---------|-------|
| DM-01 | Demo seeders are not run in production | Pass | `DatabaseSeeder::run()` wraps `OrganizationStructureSeeder`, `DemoKpiTemplateSeeder`, and `DemoUserSeeder` inside `if (app()->environment(['local', 'testing']))`. | |
| DM-02 | Production seeding process only runs required seeders | Pass | Deployment notes and production checklist both specify `--class=RolePermissionSeeder` and `--class=SuperAdminSeeder` only. | |

---

## Remaining Risks / Accepted Limitations

| # | Risk | Severity | Decision | Owner |
|---|------|---------|---------|-------|
| R-01 | No third-party error monitoring (Sentry/Flare) configured | Low | Accepted for UAT. Production checklist item 11.3 records requirement. Add before go-live. | Tech Lead |
| R-02 | `SESSION_DRIVER=file` in `.env.example` — multi-server deployments would require `redis` or `database` | Low | Acceptable for single-server UAT. Production checklist item 4.3 documents requirement. | Tech Lead |
| R-03 | Approver role cannot download evidence files (by design) | Low | Intentional: approvers approve outcomes, not raw evidence. If business requires it, add `APPROVER` to `downloadEvidence()` and cover with a test. | Product Owner |
| R-04 | No automated test for the full queue worker lifecycle (Supervisor) | Low | Queue job behaviour is tested (dispatch, completion, failure). Supervisor is an infrastructure concern, not application code. | DevOps |
| R-05 | `LOG_LEVEL=debug` in `.env.example` is verbose for production | Low | Production checklist item 11.1 requires changing to `warning` or above. | DevOps |
