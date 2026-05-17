## AI Agent Token Budget Rules

For Tailwind v4 migration work:

- Do not scan the whole repository unless explicitly requested.
- Start by reading only:
  - package.json
  - vite.config.js
  - postcss.config.js
  - tailwind.config.js
  - resources/css/app.css
  - resources/css/filament/admin/theme.css
  - app/Providers/Filament/AdminPanelProvider.php
  - docs/frontend-build-standard.md if it exists
- Do not read vendor/, node_modules/, public/build/, storage/, bootstrap/cache/, coverage/, or generated files.
- Do not inspect large lock files unless dependency resolution requires it.
- Do not paste entire file contents in responses.
- Return concise summaries and diffs only.
- Prefer targeted grep/search over opening many files.
- Ask for permission before broad repository scans.
- Apply minimal diffs only.
- Do not change business logic, policies, routes, migrations, workflows, or database schema.
