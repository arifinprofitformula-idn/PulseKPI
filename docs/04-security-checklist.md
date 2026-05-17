# PulseKPI Security Checklist

> This file lists the security requirements for PulseKPI.
> For the completed evidence-based review against these requirements, see
> [docs/10-security-review-report.md](10-security-review-report.md).

## Authentication

- Login required for all KPI pages.
- Use rate limit for login and public endpoints.

## Authorization

- Use Policy/Gate for every sensitive action.
- Employee can only view own KPI.
- Manager can only assess direct or assigned employees.
- HRD can review according to permission.
- Approver can approve according to permission.

## Input Validation

- Use Form Request for all user input.
- Validate file uploads by mime, extension, and size.

## File Security

- Evidence files must use private disk.
- Do not expose evidence files through public URL.
- Serve evidence through authorized controller.

## Data Integrity

- Approved and locked KPI cannot be edited without explicit permission.
- Use database transaction for submit, review, approve, reject, and lock.

## Audit Trail

Audit these events:

- create
- update
- delete
- submit
- review
- approve
- reject
- lock
- export
