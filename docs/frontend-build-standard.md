# PulseKPI Frontend Build Standard

## Decision Status

PulseKPI now uses a split frontend standard after Phase 1.

App, guest, and Breeze frontend assets use a Tailwind CSS v4-style pipeline.

Filament admin remains on the existing runtime/default CSS plus override-only admin CSS.

Do not treat the repository as full Tailwind v4 yet.

## Active Standard

### App / Guest / Breeze UI

- Uses a Tailwind CSS v4-style pipeline.
- Main CSS entry is [resources/css/app.css](C:/laragon/www/pulsekpi/resources/css/app.css).
- Uses `@import "tailwindcss"`.
- Uses the Vite Tailwind plugin from `@tailwindcss/vite`.
- Keeps [tailwind.config.js](C:/laragon/www/pulsekpi/tailwind.config.js) only for app theme compatibility.
- Uses [postcss.config.js](C:/laragon/www/pulsekpi/postcss.config.js) only for non-Tailwind PostCSS plugins.

### Filament Admin

- Uses Filament runtime/default CSS as the base admin theme.
- The published Filament base CSS currently exists at [public/css/filament/filament/app.css](C:/laragon/www/pulsekpi/public/css/filament/filament/app.css).
- Custom PulseKPI admin styles are loaded only as lightweight visual overrides.
- The admin override file is [resources/css/filament/admin/theme.css](C:/laragon/www/pulsekpi/resources/css/filament/admin/theme.css).
- The override is injected through [app/Providers/Filament/AdminPanelProvider.php](C:/laragon/www/pulsekpi/app/Providers/Filament/AdminPanelProvider.php) using `PanelsRenderHook::STYLES_AFTER`.

## Why This Standard Exists

Filament theme scaffolding is aligned with Tailwind CSS v4-style sources such as:

- `@import 'tailwindcss'`
- `@source ...`
- Filament source theme imports from `vendor/filament/.../resources/css/theme.css`

Even after Phase 1, the app pipeline and the admin pipeline are intentionally different. Compiling Filament source theme files through the app build can still break the admin panel layout and produce partially unstyled UI on:

- `/admin`
- `/admin/login`
- Filament pages, widgets, tables, forms, and global search

## Approved Rules

### Allowed

- Continue using [resources/css/app.css](C:/laragon/www/pulsekpi/resources/css/app.css) for guest/app frontend styling.
- Continue using the Tailwind v4 app pipeline only for app/guest/Breeze assets.
- Continue using [resources/css/filament/admin/theme.css](C:/laragon/www/pulsekpi/resources/css/filament/admin/theme.css) for admin-only visual overrides.
- Continue loading admin override CSS through `PanelsRenderHook::STYLES_AFTER`.
- Continue using Filament default runtime CSS as the admin base theme.

### Not Allowed

Do not compile Filament source theme through the current app pipeline unless the project is fully migrated to Tailwind CSS v4.

Do not treat `resources/css/filament/admin/theme.css` as a Tailwind source file in Phase 1.

Do not modify [app/Providers/Filament/AdminPanelProvider.php](C:/laragon/www/pulsekpi/app/Providers/Filament/AdminPanelProvider.php) during Phase 1.

Do not add or re-enable:

```php
->viteTheme('resources/css/filament/admin/theme.css')
```

Do not add to [resources/css/filament/admin/theme.css](C:/laragon/www/pulsekpi/resources/css/filament/admin/theme.css):

```css
@import '../../../../vendor/filament/filament/resources/css/theme.css';
```

Do not add to admin theme files:

```css
@import 'tailwindcss';
@source ...
```

Do not assume `@tailwindcss/vite` in `package.json` means the project is already migrated to Tailwind CSS v4.

## Change Safety Rules

When editing frontend or admin UI:

- Keep guest/app CSS and Filament admin CSS as separate concerns.
- Treat Filament base theme as external runtime CSS.
- Treat `resources/css/filament/admin/theme.css` as override-only.
- Avoid replacing Filament structural layout rules unless there is a tested reason.
- Prefer scoped admin selectors over broad global overrides.

## Required Checks After Admin UI Changes

After changing admin UI or admin CSS, verify:

1. `/admin/login`
2. `/admin`
3. one Filament resource list page
4. one Filament form page
5. mobile width around `390px`
6. tablet width around `768px`
7. desktop width around `1280px`

At minimum, ensure:

- sidebar renders correctly
- topbar renders correctly
- global search is styled correctly
- cards, tables, and forms are not unstyled
- there is no horizontal overflow on common pages

## Migration Trigger

The project may be treated as full Tailwind CSS v4 only when all of the following are intentionally completed together:

- `tailwindcss` dependency is upgraded to the intended v4 toolchain
- frontend app CSS entry is migrated from `@tailwind ...` directives to the approved v4 approach
- PostCSS and Vite setup are aligned with the chosen v4 pipeline
- Filament admin theme integration is migrated without fallback runtime/theme mismatch
- guest, auth, and admin layouts are regression-tested after migration

Until then, PulseKPI must be treated as a controlled split build: Tailwind v4 for app/guest/Breeze, Filament runtime/default CSS plus override-only admin styling for admin.
