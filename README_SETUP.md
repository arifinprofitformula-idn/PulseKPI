# PulseKPI Starter Pack Setup

Copy all files and folders in this starter pack into the Laravel project root.

## Included Files

- .vscode/settings.json
- .vscode/extensions.json
- .vscode/tasks.json
- .vscode/launch.json
- .editorconfig
- pint.json
- phpstan.neon
- AGENTS.md
- docs/*.md
- http/*.http
- .github/workflows/tests.yml

## Recommended Commands

Install recommended packages:

```bash
composer require filament/filament spatie/laravel-permission spatie/laravel-activitylog maatwebsite/excel laravel/horizon
composer require pestphp/pest pestphp/pest-plugin-laravel larastan/larastan laravel/pint --dev
```

Install Pest:

```bash
php artisan pest:install
```

Install Filament:

```bash
php artisan filament:install --panels
```

Install Spatie Permission:

```bash
php artisan vendor:publish --provider="Spatie\\Permission\\PermissionServiceProvider"
php artisan migrate
```

Install Horizon:

```bash
php artisan horizon:install
php artisan migrate
```

Quality gate:

```bash
composer pint
php artisan test
./vendor/bin/phpstan analyse
npm run build
```

## Notes

Adjust `php.validate.executablePath` in `.vscode/settings.json` if VS Code cannot detect your PHP runtime.

If using Windows without WSL, replace commands like `./vendor/bin/pint` with `vendor/bin/pint`.
