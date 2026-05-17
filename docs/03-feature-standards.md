# PulseKPI Feature Standards

Every feature should include relevant files from this list:

- migration
- model
- enum
- policy
- form request
- action/service
- controller or Filament resource
- factory
- seeder
- Pest tests

## Controller Rules

Controllers must be thin.

Allowed in controller:

- receive request
- authorize through Form Request or policy
- call Action/Service
- return response

Not allowed in controller:

- complex calculation
- business workflow
- long database transaction logic
- export generation logic

## Filament Rules

Filament resources are for interface composition only.

Put workflow logic in Actions or Services.

## Naming Rules

Action classes:

- CreateKpiTemplateAction
- AssignKpiTemplateAction
- SubmitKpiAssessmentAction
- ReviewKpiAssessmentAction
- ApproveKpiAssessmentAction
- CalculateKpiScoreAction

Service classes:

- KpiScoreCalculator
- KpiDashboardService
- KpiExportService
