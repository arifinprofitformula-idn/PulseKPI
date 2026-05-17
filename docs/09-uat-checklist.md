# PulseKPI UAT Checklist

**Environment:** Staging / UAT server  
**Build:** <!-- git tag or commit SHA -->  
**Test date:** <!-- YYYY-MM-DD -->  
**Tested by:** <!-- name -->  

Mark each row **Pass**, **Fail**, or **Skip** (with reason) in the **Status** column.  
Record any unexpected behaviour in **Notes**.

---

## Legend

| Symbol | Meaning |
|--------|---------|
| SA | Super Admin |
| HRD | Human Resources / HR role |
| MGR | Manager |
| EMP | Employee |
| APR | Approver |

---

## Section 1 — Authentication

| ID | Role | Scenario | Steps | Expected Result | Status | Notes |
|----|------|----------|-------|----------------|--------|-------|
| A-01 | Guest | Unauthenticated access to admin panel | Navigate to `/admin` without logging in | Redirected to `/admin/login` | | |
| A-02 | Guest | Unauthenticated access to KPI dashboard | Navigate to `/my/kpi-dashboard` | Redirected to login | | |
| A-03 | All | Login with valid credentials | Go to `/admin/login`, enter correct email and password | Redirected to admin dashboard | | |
| A-04 | All | Login with wrong password | Enter incorrect password | Error message shown, not logged in | | |
| A-05 | All | Logout | Click logout | Session cleared, redirected to login | | |
| A-06 | All | Access protected route after logout | Log out, then navigate to `/admin` | Redirected to login | | |

---

## Section 2 — Organization Setup (Super Admin / HRD)

| ID | Role | Scenario | Steps | Expected Result | Status | Notes |
|----|------|----------|-------|----------------|--------|-------|
| O-01 | SA | Create a Division | Navigate to Admin → Divisions → Create, enter name | Division saved and listed | | |
| O-02 | SA | Create a Department under the Division | Admin → Departments → Create, select Division | Department saved | | |
| O-03 | SA | Create a Position under the Department | Admin → Positions → Create, select Department | Position saved | | |
| O-04 | SA | Create an Employee user | Admin → Users → Create, assign Division/Department/Position/Supervisor | User created with correct org fields | | |
| O-05 | SA | Create a Manager user | Admin → Users → Create, assign Manager role | User with Manager role created | | |
| O-06 | SA | Assign a Supervisor to an Employee | Edit employee user, set Supervisor field to a Manager | Supervisor relationship saved | | |
| O-07 | SA | Attempt to assign employee as own supervisor | Set Supervisor to the same user | Validation error: cannot be own supervisor | | |
| O-08 | EMP | Employee cannot access organization management | Log in as Employee, navigate to `/admin/divisions` | 403 Forbidden | | |

---

## Section 3 — KPI Template Engine (HRD)

| ID | Role | Scenario | Steps | Expected Result | Status | Notes |
|----|------|----------|-------|----------------|--------|-------|
| T-01 | HRD | Create a KPI template | Admin → KPI Templates → Create, fill fields | Template saved as draft (no published_at) | | |
| T-02 | HRD | Add items to a template | Open template → Items tab → Add item with weight | Item saved with correct weight | | |
| T-03 | HRD | Add score rules to a template item | Open item → Score Rules → Add rules for score 0, 1, 2 | Score rules saved | | |
| T-04 | HRD | Weights must total 100% before publish | Set items with weights not summing to 100, click Publish | Validation error: weights must total 100% | | |
| T-05 | HRD | Publish a valid template | Ensure weights = 100%, click Publish | Template gets `published_at` timestamp, status shows Published | | |
| T-06 | HRD | Published template cannot be edited | Try to edit a published template's items or weight | Edit form is read-only / action blocked | | |
| T-07 | HRD | Duplicate a published template | Click Duplicate on a published template | New draft template created with same items and a new revision number | | |
| T-08 | MGR | Manager can view published templates | Log in as Manager, navigate to KPI Templates | Published active templates are visible | | |
| T-09 | EMP | Employee cannot access template management | Log in as Employee, navigate to `/admin/kpi-templates` | 403 Forbidden | | |

---

## Section 4 — KPI Assignment (HRD)

| ID | Role | Scenario | Steps | Expected Result | Status | Notes |
|----|------|----------|-------|----------------|--------|-------|
| AS-01 | HRD | Assign a published template to an employee | Admin → KPI Assignments → Create, select period, template, employee | Assignment created with status ASSIGNED | | |
| AS-02 | HRD | Cannot assign a draft template | Select a draft (unpublished) template in the assignment form | Validation error | | |
| AS-03 | HRD | Cannot assign to an inactive period | Select an inactive period | Validation error | | |
| AS-04 | HRD | Duplicate assignment is rejected | Assign the same template + period + employee a second time | Validation error: duplicate assignment | | |
| AS-05 | HRD | Bulk assignment to a Division | Use Bulk Assign, select Division and template | All eligible employees in Division receive an assignment | | |
| AS-06 | HRD | Cancel an assignment with no assessment | Cancel an assignment that has no linked assessment | Status becomes CANCELLED | | |
| AS-07 | HRD | Cannot cancel an assignment that has an assessment | Create an assessment for an assignment, then try to cancel the assignment | Validation error: assessment exists | | |
| AS-08 | MGR | Manager can view own team's assignments | Log in as Manager, navigate to KPI Assignments | Only direct subordinate assignments are visible | | |
| AS-09 | EMP | Employee can view own assignment | Log in as Employee, navigate to KPI Assignments | Only own assignments are visible | | |
| AS-10 | EMP | Employee cannot view another employee's assignment | Attempt to access another employee's assignment detail | 403 or 404 | | |

---

## Section 5 — KPI Assessment (Manager)

| ID | Role | Scenario | Steps | Expected Result | Status | Notes |
|----|------|----------|-------|----------------|--------|-------|
| AE-01 | MGR | Manager creates assessment for direct subordinate | Admin → KPI Assessments → Create, select subordinate's assignment | Assessment created in DRAFT status with items copied from template | | |
| AE-02 | MGR | Manager cannot create assessment for unrelated employee | Select an assignment for an employee not under this manager | 403 Forbidden | | |
| AE-03 | MGR | Score each item | Open assessment, enter actual value and score (0/1/2) for each item | Weighted scores and KPI score update automatically | | |
| AE-04 | MGR | Upload evidence file | On an assessment item, upload a PDF evidence file | File saved to private storage, file name visible | | |
| AE-05 | MGR | Evidence file download as manager | Click download on an evidence file | File downloads successfully | | |
| AE-06 | EMP | Evidence file download as employee | Employee opens their own assessment and downloads evidence | File downloads successfully | | |
| AE-07 | SA | Super Admin can download evidence | Log in as Super Admin, open an assessment, download evidence | File downloads successfully | | |
| AE-08 | EMP | Unrelated employee cannot download evidence | As an unrelated employee, attempt to access evidence download URL | 403 Forbidden | | |
| AE-09 | MGR | Enter attendance adjustment | Fill in sick, permission, absent, leave days | Attendance deduction and final score recalculate correctly | | |
| AE-10 | MGR | Cannot submit without scoring all required items | Leave one required item unscored, click Submit | Validation error: all required items must be scored | | |
| AE-11 | MGR | Submit a complete assessment | Score all items, click Submit | Status changes to SUBMITTED, submitted_at is set | | |
| AE-12 | MGR | Cannot edit a submitted assessment | Try to update item scores after submission | 403 Forbidden | | |
| AE-13 | EMP | Employee can view own assessment | Log in as Employee, navigate to `/my/kpi-assessments/{id}` | Own assessment is visible | | |
| AE-14 | EMP | Employee cannot view another employee's assessment | Attempt to access another assessment | 403 or 404 | | |

---

## Section 6 — Approval Workflow (HRD → Approver)

| ID | Role | Scenario | Steps | Expected Result | Status | Notes |
|----|------|----------|-------|----------------|--------|-------|
| WF-01 | HRD | HRD reviews a submitted assessment | Open a SUBMITTED assessment, click Review, add notes | Status changes to REVIEWED, reviewed_at is set | | |
| WF-02 | HRD | HRD cannot review an assessment in DRAFT status | Attempt to review a DRAFT assessment | Review action not available | | |
| WF-03 | HRD | HRD can reject a submitted assessment | Click Reject on a SUBMITTED assessment, enter reason | Status changes to REJECTED, rejected_at is set | | |
| WF-04 | APR | Approver sees only REVIEWED/APPROVED/LOCKED assessments | Log in as Approver, open KPI Assessments list | DRAFT and SUBMITTED assessments are not visible | | |
| WF-05 | APR | Approver approves a reviewed assessment | Click Approve on a REVIEWED assessment, add notes | Status changes to APPROVED, approved_at is set | | |
| WF-06 | APR | Approver rejects a reviewed assessment | Click Reject on a REVIEWED assessment, enter reason | Status changes to REJECTED | | |
| WF-07 | APR | Approver cannot approve a SUBMITTED assessment | Attempt to approve a SUBMITTED assessment | Approve action not available | | |
| WF-08 | APR | Approver locks an approved assessment | Click Lock on an APPROVED assessment | Status changes to LOCKED, locked_at is set | | |
| WF-09 | All | Locked assessment cannot be edited | Attempt to update scores or notes on a LOCKED assessment | Edit action disabled / 403 | | |
| WF-10 | MGR | Rejected assessment can be edited and resubmitted | Edit a REJECTED assessment, update scores, click Submit | Status changes to SUBMITTED again | | |
| WF-11 | All | Approval history is visible | Open any assessment that has been reviewed/approved | Approval history table shows actor, action, timestamp, notes | | |

---

## Section 7 — Dashboard and Reports

| ID | Role | Scenario | Steps | Expected Result | Status | Notes |
|----|------|----------|-------|----------------|--------|-------|
| DR-01 | EMP | Employee sees own KPI dashboard | Log in as Employee, navigate to `/my/kpi-dashboard` | Own latest assignment and recent assessments shown, no other employee data | | |
| DR-02 | MGR | Manager sees team-scoped dashboard | Log in as Manager, view KPI Dashboard widget | Counts reflect only direct subordinate assessments | | |
| DR-03 | HRD | HRD sees organisation-wide dashboard | Log in as HRD, view KPI Dashboard | Counts reflect all assessments | | |
| DR-04 | APR | Approver dashboard shows only REVIEWED/APPROVED/LOCKED | Log in as Approver, view KPI Dashboard | DRAFT and SUBMITTED counts are 0 | | |
| DR-05 | HRD | HRD accesses KPI Assessment Report page | Admin → KPI Assessment Report | All assessments visible, filterable | | |
| DR-06 | MGR | Manager's report shows only direct subordinates | Admin → KPI Assessment Report | Only own team's rows visible | | |
| DR-07 | EMP | Employee cannot access KPI Assessment Report page | Log in as Employee, navigate to `/admin/kpi-assessment-reports` | 403 Forbidden | | |
| DR-08 | HRD | HRD filters report by period | Apply period filter | Only assessments from that period are shown | | |
| DR-09 | HRD | HRD filters report by grade | Apply grade filter (e.g., Excellent) | Only matching assessments are shown | | |

---

## Section 8 — Export

| ID | Role | Scenario | Steps | Expected Result | Status | Notes |
|----|------|----------|-------|----------------|--------|-------|
| EX-01 | HRD | HRD requests an Excel export | Admin → KPI Assessment Report → Export, select XLSX | Export record created with status PENDING, job dispatched | | |
| EX-02 | HRD | Export completes and is downloadable | Wait for queue worker to process | Export status changes to COMPLETED, download link active | | |
| EX-03 | HRD | Download the completed export | Click Download | XLSX file downloads successfully | | |
| EX-04 | MGR | Manager export is scoped to direct subordinates | Manager requests export | Downloaded file contains only own team's rows | | |
| EX-05 | MGR | Manager cannot download another manager's export | Attempt to access another manager's export download URL | 403 Forbidden | | |
| EX-06 | EMP | Employee cannot request an organisation export | Log in as Employee, attempt to use the export action | 403 Forbidden | | |
| EX-07 | HRD | PDF export of a single assessment | Admin → KPI Assessments → Open assessment → Export PDF | PDF downloads with assessment details, approval history | | |
| EX-08 | All | PDF does not contain raw evidence file paths | Open the downloaded PDF | No `storage/app/private/...` path strings visible in the PDF | | |
| EX-09 | MGR | PENDING or FAILED export cannot be downloaded | Attempt to download an export with status PENDING or FAILED | 403 Forbidden | | |
| EX-10 | All | Missing export file returns 404 | Manually delete an export file, then try to download | 404 Not Found | | |

---

## Section 9 — Role Access Denial Cases

| ID | Requesting Role | Protected Resource | Expected |  Status | Notes |
|----|----------------|--------------------|---------|---------|-------|
| RD-01 | EMP | `/admin/kpi-assignments/create` | 403 |  | |
| RD-02 | EMP | `/admin/kpi-templates` | 403 |  | |
| RD-03 | EMP | `/admin/kpi-assessment-reports` | 403 |  | |
| RD-04 | MGR | Another manager's assessment detail | 404 |  | |
| RD-05 | APR | DRAFT or SUBMITTED assessment detail | Not visible in list / 404 |  | |
| RD-06 | APR | PDF export of a DRAFT assessment | 403 |  | |
| RD-07 | APR | PDF export of a SUBMITTED assessment | 403 |  | |
| RD-08 | MGR | Cancel an assignment with an existing assessment | Validation error |  | |
| RD-09 | Any non-owner | Export download belonging to another user | 403 |  | |
| RD-10 | Any non-owner | Evidence file belonging to unrelated assessment | 403 |  | |

---

## Sign-off

| Role | Name | Date | Result |
|------|------|------|--------|
| UAT Lead | | | Pass / Fail |
| HRD Representative | | | Pass / Fail |
| Manager Representative | | | Pass / Fail |
| Employee Representative | | | Pass / Fail |
| Approver Representative | | | Pass / Fail |
| Technical Lead | | | Pass / Fail |
