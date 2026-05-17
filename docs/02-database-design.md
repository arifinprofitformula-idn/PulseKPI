# PulseKPI Database Design Notes

## Main Entities

- users
- divisions
- departments
- positions
- kpi_templates
- kpi_template_items
- kpi_score_rules
- kpi_assignments
- kpi_assessments
- kpi_assessment_items
- kpi_attendance_adjustments
- kpi_approvals
- kpi_exports
- activity_log

## Main Relationships

- User belongs to Position
- Position belongs to Department
- Department belongs to Division
- KPI Template has many KPI Template Items
- KPI Template Item has many Score Rules
- KPI Assignment belongs to User
- KPI Assignment belongs to KPI Template
- KPI Assessment belongs to KPI Assignment
- KPI Assessment has many KPI Assessment Items
- KPI Assessment has one Attendance Adjustment
- KPI Assessment has many KPI Approvals

## Index Baseline

Add indexes for:

- year
- month
- status
- employee_id
- assessor_id
- reviewer_id
- approver_id
- division_id
- department_id
- position_id
- kpi_template_id
- kpi_assignment_id
