# Render deployment guide

This project is Laravel 12 with PHP 8.2 or newer, Vue 3, Vite, Tailwind CSS, and MySQL. The included Docker setup builds the frontend and serves Laravel's `public/` directory with Apache. `render.yaml` defines a paid Render Docker web service, an HTTP health check, and a persistent disk for complaint attachments.

## Prerequisites

- A GitHub repository connected to Render.
- An externally managed MySQL database reachable from Render. Render does not provide a managed MySQL service.
- An SMTP account for email verification and password reset messages.
- A Render paid web-service plan for the persistent attachment disk.

## Files and service configuration

The repository includes `Dockerfile`, `.dockerignore`, `php-production.ini`, `render-entrypoint.sh`, and `render.yaml`. In Render, create a Blueprint from this repository and select `render.yaml`. It creates a Docker web service named `career-next`, listens on Render's `PORT`, serves from `public/`, and checks `/up`.

The Blueprint attaches a 1 GB persistent disk at `storage/app/private`, where the application's private complaint attachments are written. Render persistent disks require a paid service plan, are available to one instance, and prevent zero-downtime deploys. Increase the disk size before it fills. Do not scale this service to multiple instances while using this disk.

## Environment variables

The Blueprint sets safe production defaults and prompts for values marked `sync: false`. Set these in Render's Environment page without putting credentials in Git:

| Variable | Value or purpose |
| --- | --- |
| `APP_ENV` | `production` (set by the Blueprint) |
| `APP_DEBUG` | `false` (set by the Blueprint) |
| `APP_KEY` | Generated once by the Blueprint and retained across deploys. If setting it manually, generate with `php artisan key:generate --show` and store the result as a secret. Never regenerate it during routine deploys. |
| `APP_URL` | The deployed HTTPS URL, for example `https://career-next.onrender.com`. |
| `DB_CONNECTION` | `mysql` (set by the Blueprint) |
| `DB_HOST` | Hostname from the external MySQL provider. |
| `DB_PORT` | MySQL port, normally `3306` (set by the Blueprint). |
| `DB_DATABASE` | Production database name. |
| `DB_USERNAME` | A least-privilege database user for this application. |
| `DB_PASSWORD` | The database user's password. |
| `SESSION_DRIVER` | `database` (set by the Blueprint; the session table is included in the migrations). |
| `SESSION_SECURE_COOKIE` | `true` (set by the Blueprint for HTTPS). |
| `CACHE_STORE` | `database` (set by the Blueprint; cache tables are included in the migrations). |
| `QUEUE_CONNECTION` | `database` (set by the Blueprint; job tables are included in the migrations). |
| `FILESYSTEM_DISK` | `local` (set by the Blueprint; the persistent disk stores private uploads). |
| `MAIL_HOST` | SMTP server hostname. |
| `MAIL_PORT` | SMTP port, usually `587` for TLS (set by the Blueprint). |
| `MAIL_USERNAME` | SMTP account username. |
| `MAIL_PASSWORD` | SMTP account password or app password. |
| `MAIL_FROM_ADDRESS` | Verified sender address. |
| `MAIL_FROM_NAME` | Sender name (set by the Blueprint). |
| `LOG_CHANNEL` | `stderr` (set by the Blueprint so logs appear in Render). |

Render supplies `PORT`; do not set `SERVER_PORT` or hardcode a port in the dashboard. The container defaults to port `10000` only when `PORT` is absent.

## Database setup and migrations

1. Create the production MySQL database and a dedicated application user with only the permissions Laravel needs on that database.
2. Allow network access from the Render service, following the database provider's private-network or IP allow-list instructions. Require TLS if the provider supports it.
3. Set `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in Render. Confirm the database is reachable before migrating.
4. Back up the target database. Review the migration list with `php artisan migrate:status` in the Render Shell.
5. Apply outstanding migrations with `php artisan migrate --force` from the Render Shell. The current `up()` migrations create tables and indexes; no migration is run automatically on service startup. Do not use `migrate:fresh`, `migrate:reset`, or `db:wipe` against production.

The application uses MySQL locally and in the Blueprint; no database driver or schema conversion is performed. Existing local records and uploaded files are not copied to Render by deployment. Plan a separate, verified migration and backup for any production data you need to retain. PHP is configured for the application's limit of five attachments at up to 5 MB each, with multipart request headroom.

Do not run `php artisan db:seed` in production. The seeders are for development/test data and include the known `admin@example.com` / `password` demo account in non-production environments. For the first production administrator, register the account normally, then promote that account in the Render Shell with Tinker, replacing the email with the account you just registered:

```sh
php artisan tinker --execute="App\\Models\\User::where('email', 'admin@your-domain.example')->update(['role' => App\\Models\\User::ROLE_ADMIN, 'is_active' => true, 'email_verified_at' => now()]);"
```

## Build, startup, and health check

Render builds the Docker image from the repository root. The image installs locked Composer and npm dependencies, runs `npm run build`, prepares Laravel's package manifest and Blade views, then starts Apache. The entrypoint configures Apache to bind `0.0.0.0:$PORT`, sets writable Laravel storage/cache permissions, and caches configuration from the Render environment. It does not run migrations or seeders.

The health check uses Laravel's built-in `GET /up` endpoint. It confirms the application can respond over HTTP; it does not prove the MySQL database or SMTP service is reachable.

## Upload storage

Complaint attachments are private and downloaded through an authenticated, authorized API endpoint. New uploads use Laravel's configured default filesystem disk. The Render Blueprint's persistent disk backs the existing private local disk path, so attachments survive restarts and deploys. Keep the disk attached and do not scale beyond one instance while using local persistent storage. For multi-instance scaling, move uploads to a shared S3-compatible object store and install/configure the Laravel Flysystem S3 adapter after reviewing that dependency change.

## Deploy from GitHub

1. Push the reviewed deployment files to the GitHub branch you want Render to deploy.
2. In Render, create a Blueprint and connect the repository. Review the paid service plan and persistent disk size before applying it.
3. Enter the production URL, MySQL connection values, and SMTP credentials in the Render environment setup. Keep `APP_KEY` stable; if you provide it manually, set it once as a secret.
4. Wait for the Docker build and web service deploy to complete. Verify the `/up` health check passes.
5. Back up and migrate the production MySQL database as described above.
6. Register and promote the first administrator, then verify login, email delivery, complaint creation, attachment upload/download, and the theme switcher.

Render deploys automatically from the connected branch when auto-deploy is enabled. To deploy manually, use the service's Manual Deploy action and choose the intended commit.

## Logs and common problems

- **Build fails during Composer install:** check the Composer error and PHP extension requirements. Rebuild after reviewing lockfile and security findings; do not bypass Composer's security blocking to force a deploy.
- **Build fails during `npm ci`:** confirm `package-lock.json` is committed with `package.json` and retry the build.
- **Port scan or health check fails:** confirm the Docker service is running, Apache is bound to `0.0.0.0:$PORT`, and `/up` returns a 2xx response.
- **Blank page or missing assets:** inspect Render build logs for the Vite build, then browser console/network errors. The Docker image builds `public/build` before startup.
- **Database connection errors:** verify MySQL is running, the hostname/port/user/database are correct, TLS requirements are met, and the provider allows connections from Render.
- **Sessions, cache, or jobs table missing:** apply the outstanding migrations; these drivers use the database in the Blueprint.
- **Verification/reset emails are missing:** check the SMTP host, credentials, port, sender verification, and TLS scheme. The Blueprint defaults to SMTP.
- **Attachments disappear or uploads fail:** verify the persistent disk is attached at `/var/www/html/storage/app/private`, the plan supports disks, and the disk has free space.

Use the Render service's **Logs** page to inspect image-build output and runtime logs. Runtime Laravel logs are sent to standard error and Apache access logs to standard output.
