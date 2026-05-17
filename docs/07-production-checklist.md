# PulseKPI Production Checklist

Complete every item on this list before going live or deploying a new release to production.
Mark each item with `[x]` when confirmed. Leave a note in the **Notes** column for anything
that requires an explanation or a follow-up ticket.

---

## 1. Environment Configuration

| # | Item | Notes |
|---|------|-------|
| 1.1 | `APP_ENV=production` is set | |
| 1.2 | `APP_DEBUG=false` is set | Exposing `true` leaks stack traces and config values |
| 1.3 | `APP_KEY` is set and is 32-character base64 string (`php artisan key:generate`) | Never reuse a development key |
| 1.4 | `APP_URL` matches the production domain including scheme (`https://`) | |
| 1.5 | `APP_NAME` is set to the correct product name | |

---

## 2. Database

| # | Item | Notes |
|---|------|-------|
| 2.1 | `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` are all set to production values | |
| 2.2 | Database user has only the minimum required privileges (SELECT, INSERT, UPDATE, DELETE, CREATE, DROP for migrations) | |
| 2.3 | Database connection is not exposed to the public internet | |

---

## 3. Cache and Queue

| # | Item | Notes |
|---|------|-------|
| 3.1 | `CACHE_STORE=redis` is set | |
| 3.2 | `QUEUE_CONNECTION=redis` is set | |
| 3.3 | Redis host, port, and password are set for the production Redis instance | |
| 3.4 | A queue worker process is running and managed by Supervisor (see `docs/08-deployment-notes.md`) | |
| 3.5 | Failed jobs table exists and is monitored (`failed_jobs`) | |
| 3.6 | Laravel Horizon is configured if used, or a Supervisor-managed `queue:work` is sufficient | |

---

## 4. Session and Cookie Security

| # | Item | Notes |
|---|------|-------|
| 4.1 | `SESSION_SECURE_COOKIE=true` — session cookies are only sent over HTTPS | Required when SSL is active |
| 4.2 | `SESSION_ENCRYPT=true` — session data is encrypted at rest | |
| 4.3 | `SESSION_DRIVER` is set to `redis` or `database` (not `file` for multi-server deployments) | |
| 4.4 | `SESSION_LIFETIME` is appropriate for the expected user session duration | |
| 4.5 | `SESSION_DOMAIN` is set to the production domain if needed | |

---

## 5. SSL / HTTPS

| # | Item | Notes |
|---|------|-------|
| 5.1 | A valid SSL certificate is installed and active | |
| 5.2 | HTTP traffic is redirected to HTTPS at the web server level | |
| 5.3 | HSTS is configured if required by your security policy | |

---

## 6. File Storage

| # | Item | Notes |
|---|------|-------|
| 6.1 | `storage/` and `bootstrap/cache/` are writable by the web server user | Run `chmod -R 775 storage bootstrap/cache` |
| 6.2 | `php artisan storage:link` has been run to create the `public/storage` symlink | Only needed if public disk is used for public assets |
| 6.3 | Private evidence files (`storage/app/private/evidence/`) are **not** web-accessible | Confirm the web server does not serve the `storage/app/private/` path |
| 6.4 | Private export files (`storage/app/private/exports/`) are **not** web-accessible | Same as above |
| 6.5 | `KPI_ASSESSMENT_EVIDENCE_DISK` and `KPI_REPORT_EXPORT_DISK` are both set to `local` (or a private S3 bucket) | Never set these to `public` |

---

## 7. Super Admin Account

| # | Item | Notes |
|---|------|-------|
| 7.1 | `SUPER_ADMIN_NAME`, `SUPER_ADMIN_EMAIL`, and `SUPER_ADMIN_PASSWORD` are all set in production `.env` | `SUPER_ADMIN_PASSWORD` has no fallback — seeder will fail if unset |
| 7.2 | `SUPER_ADMIN_PASSWORD` is a strong, unique password that is not `ChangeMe123!` or any default | |
| 7.3 | The super admin password has been changed from any value used in staging or demo | |
| 7.4 | The super admin account email is a real, monitored mailbox | |

---

## 8. Seeders

| # | Item | Notes |
|---|------|-------|
| 8.1 | Only `RolePermissionSeeder` and `SuperAdminSeeder` are run in production | `DatabaseSeeder` gates demo seeders behind `app()->environment(['local', 'testing'])` |
| 8.2 | Demo seeders (`OrganizationStructureSeeder`, `DemoKpiTemplateSeeder`, `DemoUserSeeder`) have **not** been run | Confirm with `php artisan db:table` or a manual audit |
| 8.3 | `php artisan db:seed` has been run with only the required seeders if re-seeding an existing production DB | |

---

## 9. Deployment Commands

Run these in order after every deployment:

| # | Command | Purpose |
|---|---------|---------|
| 9.1 | `composer install --no-dev --optimize-autoloader` | Install production dependencies |
| 9.2 | `npm ci && npm run build` | Build frontend assets |
| 9.3 | `php artisan migrate --force` | Apply pending migrations |
| 9.4 | `php artisan config:cache` | Cache configuration |
| 9.5 | `php artisan route:cache` | Cache routes |
| 9.6 | `php artisan view:cache` | Cache Blade views |
| 9.7 | `php artisan optimize` | General optimization (wraps config/route/view cache) |
| 9.8 | Reload/restart the queue worker | Apply code changes to the worker process |

---

## 10. Scheduler

| # | Item | Notes |
|---|------|-------|
| 10.1 | If any scheduled tasks are used, a cron entry running `php artisan schedule:run` every minute is active | See `docs/08-deployment-notes.md` for the cron example |

---

## 11. Logging and Monitoring

| # | Item | Notes |
|---|------|-------|
| 11.1 | `LOG_CHANNEL` and `LOG_LEVEL` are appropriate for production (`warning` or above for `LOG_LEVEL`) | `debug` logs too much in production |
| 11.2 | Log files or a log aggregation service is monitored | |
| 11.3 | An error monitoring tool (e.g., Sentry, Flare, Bugsnag) is configured if required | |
| 11.4 | Failed queue jobs are alerted on or reviewed regularly | |

---

## 12. Backup

| # | Item | Notes |
|---|------|-------|
| 12.1 | Database backups are scheduled and tested | |
| 12.2 | Private file storage (`storage/app/private/`) is included in backups | |
| 12.3 | A restore test has been performed at least once | |

---

## 13. Post-Deploy Smoke Tests

| # | Scenario | Expected |
|---|----------|---------|
| 13.1 | Browse to `APP_URL` — redirected to login | Login page loads |
| 13.2 | Log in as super admin | Redirected to admin dashboard |
| 13.3 | Browse to `/admin/kpi-assessments` as super admin | Assessment list loads |
| 13.4 | Attempt to access `/admin/kpi-assessments` without login | Redirected to login |
| 13.5 | Check queue worker is processing jobs | Submit a report export and confirm it completes |
| 13.6 | Download a completed export file | File downloads successfully |
| 13.7 | Attempt to access a private evidence URL directly in the browser | Returns 404 or 403 (not the file contents) |
