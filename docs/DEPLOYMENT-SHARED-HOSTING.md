# SISURAT Shared Hosting Deployment Guide

## Runtime

- PHP 8.2 or newer with `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd` or `imagick`, `curl`, `xml`, and `ctype`.
- MySQL 8.0+ or MariaDB 10.4+ with InnoDB.
- `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=database`, and `CACHE_STORE=database` for initial shared-hosting deployment.

## Build Package

1. Run `composer install --no-dev --optimize-autoloader`.
2. Run `npm ci` and `npm run build`.
3. Upload the application outside the public web root when possible.
4. Point the domain document root to `public/`.
5. Copy `.env.example` to `.env` on the server and fill real credentials there only.
6. Run `php artisan key:generate`, `php artisan migrate --force`, `php artisan config:cache`, `php artisan route:cache`, and `php artisan view:cache`.

## Private Storage

Attachments stay under `storage/app/private` and are streamed through authenticated routes. Do not run `storage:link` for document files and do not place migrated documents under `public/`.

## Cron

Configure cPanel Cron:

```text
* * * * * /usr/local/bin/php /home/{cpanel_user}/sisurat_app/artisan schedule:run >> /dev/null 2>&1
```

Recommended direct maintenance commands when scheduler access is limited:

```text
php artisan sisurat:prune-idempotency
```

## Backups

- Nightly `mysqldump`, gzip-compressed, retained for at least 30 days.
- Nightly backup of `storage/app/private/`.
- Test restore before cutover. Target RTO: 4 hours. Target RPO: 24 hours.

## Manual Cutover Gates

- Production PHP/extensions verified in hosting control panel.
- Production database credentials created and stored only in `.env`.
- SSL enabled and `APP_URL` set to the HTTPS domain.
- Final legacy write freeze announced.
- Dry-run legacy migration report reviewed.
- Staging p95 benchmark executed for `BENCH-01` through `BENCH-05`.
