# PulseKPI Deployment Notes

This document describes how to deploy PulseKPI to a production server. It covers a typical
VPS or managed-server deployment using Nginx + PHP-FPM + MySQL/PostgreSQL + Redis.

If you are deploying to cPanel or shared hosting with terminal access, use
[docs/11-shared-hosting-deployment-guide.md](C:/laragon/www/pulsekpi/docs/11-shared-hosting-deployment-guide.md) instead.

> **Laragon / WAMP / local dev environments are not production targets.**
> These notes assume a Linux server. Adjust paths for your environment.

---

## 1. Assumptions

- **OS:** Ubuntu 22.04 LTS or equivalent
- **Web server:** Nginx (Apache config not covered here)
- **PHP:** 8.3+ with extensions: `pdo`, `pdo_mysql` (or `pdo_pgsql`), `redis`, `gd`, `zip`, `mbstring`, `xml`, `curl`, `bcmath`, `fileinfo`
- **Database:** MySQL 8 or PostgreSQL 15
- **Cache / Queue:** Redis 7+
- **Node.js:** 20+ (for `npm run build`)
- **Composer:** 2.x
- **Deployment tool:** Manual SSH, Laravel Forge, Ploi, or Envoyer — all are compatible

---

## 2. Required Server Packages

```bash
# PHP 8.3 and extensions
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-pgsql \
    php8.3-redis php8.3-gd php8.3-zip php8.3-mbstring php8.3-xml \
    php8.3-curl php8.3-bcmath php8.3-fileinfo

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js (via nvm or NodeSource)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Redis
sudo apt install -y redis-server
```

---

## 3. First-Time Deployment

### 3.1 Clone the repository

```bash
cd /var/www
git clone <repository-url> pulsekpi
cd pulsekpi
```

### 3.2 Install PHP dependencies (production only)

```bash
composer install --no-dev --optimize-autoloader
```

### 3.3 Copy and configure the environment file

```bash
cp .env.example .env
```

Edit `.env` and set **all** values. Minimum required changes from the example:

| Key | Production value |
|-----|-----------------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Generate with `php artisan key:generate` |
| `APP_URL` | Your HTTPS domain, e.g. `https://kpi.example.com` |
| `DB_*` | Production database credentials |
| `REDIS_*` | Production Redis credentials |
| `SESSION_SECURE_COOKIE` | `true` |
| `SESSION_ENCRYPT` | `true` |
| `SESSION_DRIVER` | `redis` |
| `SUPER_ADMIN_EMAIL` | A real monitored mailbox |
| `SUPER_ADMIN_PASSWORD` | A strong, unique password |
| `MAIL_*` | Production SMTP or SES settings |

### 3.4 Generate the application key

```bash
php artisan key:generate
```

### 3.5 Build frontend assets

```bash
npm ci
npm run build
```

### 3.6 Run database migrations and seed required data

```bash
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=SuperAdminSeeder
```

> **Do not run `php artisan db:seed` without `--class`.** The plain seeder command runs
> `DatabaseSeeder`, which is gated to skip demo seeders in production, but explicit class
> targeting is safer and leaves no ambiguity.

### 3.7 Create the public storage symlink

```bash
php artisan storage:link
```

This links `public/storage` → `storage/app/public`. Only used for public assets.
Private evidence and export files are stored under `storage/app/private/` and are
served exclusively through authorized controllers — never via a public URL.

### 3.8 Set directory permissions

```bash
sudo chown -R www-data:www-data /var/www/pulsekpi
sudo chmod -R 775 /var/www/pulsekpi/storage
sudo chmod -R 775 /var/www/pulsekpi/bootstrap/cache
```

### 3.9 Cache application configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Or use the convenience command:

```bash
php artisan optimize
```

---

## 4. Subsequent Deployments (Zero-Downtime Recommended)

```bash
# Pull latest code
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader

# Rebuild frontend assets
npm ci && npm run build

# Apply schema changes
php artisan migrate --force

# Clear and rebuild caches
php artisan optimize

# Restart the queue worker (see Section 6)
sudo supervisorctl restart pulsekpi-worker:*
```

---

## 5. Nginx Configuration

```nginx
server {
    listen 80;
    server_name kpi.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name kpi.example.com;

    ssl_certificate     /etc/ssl/certs/kpi.example.com.crt;
    ssl_certificate_key /etc/ssl/private/kpi.example.com.key;

    root /var/www/pulsekpi/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Block direct access to private storage
    location ^~ /storage/app/private/ {
        deny all;
        return 403;
    }
}
```

> The `storage/app/private/` block is a defence-in-depth measure. Laravel itself never
> serves this path publicly, but the explicit Nginx deny ensures web server misconfiguration
> cannot expose private files.

---

## 6. Queue Worker (Supervisor)

PulseKPI uses queued jobs for report exports and email notifications.
A queue worker must be running at all times in production.

### Supervisor configuration

Create `/etc/supervisor/conf.d/pulsekpi-worker.conf`:

```ini
[program:pulsekpi-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/pulsekpi/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/pulsekpi/storage/logs/worker.log
stopwaitsecs=3600
```

Apply and start:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start pulsekpi-worker:*
```

After every deployment, restart the worker so it picks up code changes:

```bash
sudo supervisorctl restart pulsekpi-worker:*
```

### Failed jobs

Monitor the `failed_jobs` table regularly:

```bash
php artisan queue:failed
```

To retry all failed jobs:

```bash
php artisan queue:retry all
```

---

## 7. Task Scheduler

If scheduled tasks are used (e.g., cache clearing or periodic reports), add this cron entry
for the `www-data` user:

```cron
* * * * * cd /var/www/pulsekpi && php artisan schedule:run >> /dev/null 2>&1
```

---

## 8. Private File Storage Notes

Evidence files and export files are stored on the `local` disk, which maps to
`storage/app/private/` by default. This directory is:

- Outside the web root (`public/`)
- Never exposed by a public URL
- Served exclusively through `KpiAssessmentEvidenceDownloadController` and
  `KpiReportExportDownloadController`, both of which enforce authorization before streaming

**Do not change `KPI_ASSESSMENT_EVIDENCE_DISK` or `KPI_REPORT_EXPORT_DISK` to `public`.**
If you need S3 or another cloud disk for file storage, configure a private bucket and
set these env vars to the private disk name. The download controllers use
`Storage::disk($disk)->download(...)` and will work with any configured private disk.

---

## 9. Rollback Notes

If a deployment introduces a critical regression:

1. **Code rollback:** Revert to the previous Git tag or release branch.
2. **Migration rollback:** Run `php artisan migrate:rollback` to undo the most recent
   migration batch. All migrations include a `down()` method.
   - Test rollback on staging before applying to production.
   - The Phase 4 hardening migration (`update_kpi_assessment_items_for_phase4_hardening`)
     has a `down()` that removes the snapshot columns and reverts the nullable score.
3. **Cache clear:** Run `php artisan optimize:clear` after rolling back.
4. **Queue worker restart:** `sudo supervisorctl restart pulsekpi-worker:*`

---

## 10. Post-Deploy Smoke Tests

See `docs/07-production-checklist.md` — Section 13 — for the full post-deploy smoke test list.

Quick checks after any deployment:

```bash
# Confirm migrations applied
php artisan migrate:status

# Confirm config cache is fresh
php artisan config:show app.env   # should output "production"

# Confirm queue worker is alive
sudo supervisorctl status pulsekpi-worker:*
```
