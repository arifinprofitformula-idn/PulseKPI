# PulseKPI Shared Hosting Deployment Guide

Panduan ini khusus untuk deploy PulseKPI ke shared hosting atau cPanel hosting yang menyediakan:

- akses terminal / SSH
- Git
- Composer
- PHP 8.2+
- MySQL
- cron job

Panduan ini tidak ditujukan untuk VPS Nginx/Redis/Supervisor penuh. Untuk server jenis itu, gunakan [docs/08-deployment-notes.md](C:/laragon/www/pulsekpi/docs/08-deployment-notes.md).

---

## 1. Kapan Panduan Ini Dipakai

Gunakan panduan ini bila kondisi hosting Anda seperti berikut:

- source code PulseKPI sudah ada di repository Git
- hosting memakai cPanel atau shared hosting Linux sejenis
- document root domain bisa diarahkan ke folder tertentu, atau minimal Anda bisa mengatur `public_html`
- Redis dan Supervisor kemungkinan tidak tersedia
- Node.js mungkin tersedia, mungkin juga tidak

Asumsi paling aman untuk shared hosting PulseKPI:

- `CACHE_STORE=file`
- `SESSION_DRIVER=file`
- `QUEUE_CONNECTION=sync`

Konfigurasi ini paling sederhana dan paling stabil untuk shared hosting.

---

## 2. Kebutuhan Minimum Server

PulseKPI saat ini membutuhkan:

- PHP `8.2+`
- ekstensi PHP umum Laravel: `mbstring`, `xml`, `curl`, `gd`, `zip`, `bcmath`, `fileinfo`, `pdo_mysql`
- MySQL / MariaDB
- Composer 2

Project ini memakai:

- Laravel 12
- Filament 5
- DomPDF
- Laravel Excel
- Spatie Permission
- Spatie Activitylog

---

## 3. Rekomendasi Struktur Folder

Contoh struktur aman di shared hosting:

```text
/home/USERNAME/
  pulsekpi/               <- full Laravel project
  public_html/            <- document root domain
```

Opsi terbaik:

- simpan source code penuh di luar `public_html`
- arahkan domain atau subdomain langsung ke folder `pulsekpi/public`

Kalau hosting tidak mengizinkan custom document root:

- simpan project di `/home/USERNAME/pulsekpi`
- pindahkan isi folder `public/` ke `public_html/`
- sesuaikan `index.php` agar mengarah ke folder project utama

Opsi kedua ini bisa jalan, tapi lebih rentan salah konfigurasi daripada custom document root.

---

## 4. Konfigurasi `.env` Production yang Disarankan

Buat file `.env` dari `.env.example`:

```bash
cp .env.example .env
```

Gunakan baseline berikut untuk shared hosting:

```env
APP_NAME=PulseKPI
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=user_database
DB_PASSWORD=password_database

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true

CACHE_STORE=file
CACHE_PREFIX=pulsekpi

QUEUE_CONNECTION=sync

FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=mail.domain-anda.com
MAIL_PORT=587
MAIL_USERNAME=email@domain-anda.com
MAIL_PASSWORD=password-email
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=email@domain-anda.com
MAIL_FROM_NAME="${APP_NAME}"

SUPER_ADMIN_NAME="PulseKPI Super Admin"
SUPER_ADMIN_EMAIL=admin@domain-anda.com
SUPER_ADMIN_PASSWORD=PasswordKuatSekali123!
```

Catatan penting:

- ubah `APP_DEBUG` menjadi `false`
- ubah `APP_URL` ke domain produksi
- jangan pakai `redis` bila hosting tidak menyediakan Redis
- jangan pakai `QUEUE_CONNECTION=redis` pada shared hosting biasa
- gunakan password super admin yang benar-benar baru

---

## 5. Deploy Pertama Kali dari Repository

Masuk ke terminal hosting:

```bash
cd ~
git clone <URL-REPOSITORY> pulsekpi
cd pulsekpi
```

Install dependency production:

```bash
composer install --no-dev --optimize-autoloader
```

Buat `.env`:

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` sesuai server Anda.

Jalankan migration:

```bash
php artisan migrate --force
```

Jalankan seeder inti:

```bash
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=SuperAdminSeeder
```

Buat symlink storage:

```bash
php artisan storage:link
```

Optimasi Laravel:

```bash
php artisan optimize
```

---

## 6. Build Frontend Asset

### Opsi A: Node.js tersedia di server

Jalankan:

```bash
npm ci
npm run build
```

### Opsi B: Node.js tidak tersedia di server

Build di lokal:

```bash
npm ci
npm run build
```

Lalu upload hasil berikut ke server:

- `public/build/`

Pastikan file berikut ikut ada:

- `public/build/manifest.json`

Tanpa `manifest.json`, Laravel Vite tidak bisa membaca asset production dengan benar.

---

## 7. Document Root Domain

### Opsi terbaik

Arahkan domain atau subdomain ke:

```text
/home/USERNAME/pulsekpi/public
```

Ini adalah pendekatan yang paling aman.

### Opsi fallback jika harus memakai `public_html`

Pindahkan isi folder:

```text
/home/USERNAME/pulsekpi/public/
```

ke:

```text
/home/USERNAME/public_html/
```

Kemudian edit `public_html/index.php`.

Contoh penyesuaian path:

```php
require __DIR__.'/../pulsekpi/vendor/autoload.php';

$app = require_once __DIR__.'/../pulsekpi/bootstrap/app.php';
```

Dan file:

```text
public_html/.htaccess
```

harus tetap ikut ter-copy dari folder `public`.

Catatan:

- jangan pindahkan folder `app`, `config`, `storage`, atau `vendor` ke dalam `public_html`
- hanya file public Laravel yang boleh ada di document root

---

## 8. Permission Folder

Pastikan folder ini writable:

- `storage/`
- `bootstrap/cache/`

Contoh command:

```bash
chmod -R 775 storage bootstrap/cache
```

Jika hosting memakai user yang sama dengan akun Anda, ini biasanya cukup.

---

## 9. Scheduler di cPanel / Cron Job

Tambahkan cron job:

```cron
* * * * * /usr/bin/php /home/USERNAME/pulsekpi/artisan schedule:run >> /dev/null 2>&1
```

Sesuaikan:

- path PHP bila berbeda
- path home user hosting Anda

Kalau belum tahu path PHP, cek:

```bash
which php
```

---

## 10. Konfigurasi Queue untuk Shared Hosting

Untuk shared hosting, gunakan:

```env
QUEUE_CONNECTION=sync
```

Alasannya:

- tidak perlu worker background permanen
- paling cocok untuk hosting tanpa Supervisor
- paling sederhana untuk stabilisasi awal

Konsekuensinya:

- proses export atau job berat berjalan langsung saat request
- beberapa aksi bisa terasa lebih lambat daripada server VPS

Kalau nanti hosting mendukung proses background yang stabil, Anda bisa pertimbangkan naik ke:

```env
QUEUE_CONNECTION=database
```

Lalu buat tabel queue:

```bash
php artisan queue:table
php artisan migrate --force
```

Tetapi untuk tahap awal, `sync` lebih aman.

---

## 11. Konfigurasi Cache dan Session

Shared hosting paling aman memakai:

```env
CACHE_STORE=file
SESSION_DRIVER=file
```

Jangan gunakan Redis jika:

- hosting tidak menyediakan Redis
- Anda tidak punya kredensial Redis yang valid

---

## 12. Urutan Deploy Update Berikutnya

Setelah ada perubahan baru di repository:

```bash
cd ~/pulsekpi
git pull origin develop
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
```

Jika build dilakukan di server:

```bash
npm ci
npm run build
```

Jika build dilakukan di lokal:

- upload ulang folder `public/build`

---

## 13. Checklist Uji Setelah Deploy

Setelah deploy selesai, cek minimal ini:

1. Homepage bisa dibuka.
2. Halaman `/admin` muncul normal.
3. Login admin berhasil.
4. Dashboard Filament tampil normal.
5. Logo, CSS, dan icon termuat benar.
6. Form login tidak rusak di mobile.
7. Export PDF/Excel bekerja jika fitur itu diuji.
8. File upload evidence berjalan jika fitur itu dipakai.
9. `storage/logs/laravel.log` tidak menunjukkan error fatal.

---

## 14. Troubleshooting Umum

### CSS atau JS tidak muncul

Penyebab umum:

- folder `public/build` belum ada
- `manifest.json` tidak ikut terupload
- domain masih menunjuk ke folder yang salah

Solusi:

```bash
php artisan optimize:clear
```

Lalu pastikan `public/build/manifest.json` tersedia.

### Error 500 setelah deploy

Cek:

```bash
php artisan about
tail -n 100 storage/logs/laravel.log
```

Biasanya penyebabnya:

- `.env` salah
- APP_KEY belum ada
- permission folder salah
- ekstensi PHP kurang

### Login berhasil tapi redirect bermasalah

Periksa:

- `APP_URL`
- `SESSION_DRIVER`
- `SESSION_SECURE_COOKIE=true`
- domain benar-benar memakai HTTPS

### Export lambat atau timeout

Ini normal jika `QUEUE_CONNECTION=sync` dan shared hosting terbatas.

Solusi bertahap:

1. kecilkan beban export
2. naikkan limit hosting bila memungkinkan
3. migrasikan ke VPS jika trafik dan export makin berat

---

## 15. Rekomendasi Operasional untuk PulseKPI

Untuk deployment shared hosting pertama, gunakan baseline berikut:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `SESSION_DRIVER=file`
- `CACHE_STORE=file`
- `QUEUE_CONNECTION=sync`
- asset build di lokal jika Node server tidak tersedia

Ini bukan setup paling kuat, tetapi paling cocok untuk shared hosting dan paling aman untuk go-live awal.

Jika nanti PulseKPI mulai dipakai lebih aktif, terutama untuk:

- export report lebih sering
- upload evidence lebih besar
- banyak user login bersamaan

maka target berikutnya sebaiknya pindah ke VPS atau managed server.
