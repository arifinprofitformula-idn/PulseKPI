# PulseKPI AI Agent Prompts

## Phase Execution Prompt

Read `AGENTS.md`, `docs/00-project-blueprint.md`, `docs/01-roadmap.md`, and `docs/05-definition-of-done.md`.

Implement only: `[PHASE_NAME OR FEATURE_NAME]`

Rules:

- Follow the roadmap phase by phase.
- Do not jump ahead to future phases.
- Use Laravel 12 conventions and the modular monolith structure.
- Keep business logic in `app/Actions` or `app/Services`.
- Use Form Requests, Policies/Gates, enums, queue jobs, and resources where relevant.
- Add Pest coverage for guest, authorization, validation, and happy path cases.
- Do not modify unrelated files.
- End with changed files, tests added, verification commands, and next recommended phase.

## Code Review Prompt

Review the current PulseKPI codebase against `AGENTS.md` and the current roadmap phase.

Focus on:

- security or authorization gaps
- validation gaps
- business logic in the wrong layer
- N+1 and indexing risks
- missing tests
- deviations from the roadmap phase boundary

Return findings first, ordered by severity, with concrete file references.

## Refactor Prompt

Refactor the selected PulseKPI code without changing behavior.

Rules:

- Preserve the current phase scope.
- Move business logic into Actions or Services.
- Improve validation, authorization, naming, and type coverage.
- Add or update Pest tests when behavior is protected by the refactor.

## Test Creation Prompt

Create Pest tests for `[FEATURE_NAME]`.

Minimum cases:

- guest cannot access
- unauthorized user cannot access
- authorized user can access
- validation fails with invalid payload
- happy path succeeds
- locked or approved records cannot be edited when applicable
