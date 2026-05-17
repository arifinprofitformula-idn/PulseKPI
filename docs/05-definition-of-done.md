# PulseKPI Definition of Done

A phase or feature is done only when the applicable business, security, and quality gates are all satisfied.

## Functional

- The scope matches the current roadmap phase.
- Happy path behavior works end to end.
- Guest and unauthorized paths are blocked.
- Empty states and validation feedback are handled clearly.

## Technical

- Controllers and Filament pages stay thin.
- Business rules live in `app/Actions` or `app/Services`.
- Validation uses Form Requests where inputs are accepted.
- Authorization uses Policies or Gates for sensitive actions.
- Status-driven workflows use enums.
- Migrations are rollback-safe and include appropriate keys and indexes.

## Security

- No secrets are hardcoded.
- Sensitive actions are authorized explicitly.
- Private evidence files are not exposed publicly.
- Approved or locked KPI data cannot be edited without permission.
- Audit trail coverage exists for critical workflow events.

## Performance

- N+1 query risks are addressed with eager loading.
- Large list views paginate.
- Report-scale operations avoid in-memory `get()->map()` patterns.
- Exports, notifications, and other heavy work are queued.

## Testing And Quality

- Pest tests cover guest access, authorization, validation, happy path, and locked-data restrictions where relevant.
- `composer pint` passes.
- `php artisan test` passes.
- `./vendor/bin/phpstan analyse --memory-limit=1G` passes.
- Unrelated files are not modified.
