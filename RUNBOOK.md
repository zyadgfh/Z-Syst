# Z-Syst Pharmacy — Runbook

> **What this is:** A practical ops/engineering runbook for the Z-Syst pharmacy system.
> Covers setup, common issues, the database trap that bit us, deployment notes, and how
> to roll forward.

Last updated: 2026-07-29

---

## 1. Quick Start (Local Dev)

```powershell
# 1. Install PHP 8.1+ (already present: PHP 8.3.30)
php --version

# 2. Install Composer deps
composer install

# 3. Copy env & generate key
cp .env.example .env
php artisan key:generate

# 4. Make sure MySQL is up (see §2)
# 5. Run migrations
php artisan migrate --seed
php artisan storage:link
```

Default seeded user: `admin@gmail.com` / `password`

---

## 2. MySQL — The "Connection Refused" Trap

**Symptom:**

```
SQLSTATE[HY000] [2002] No connection could be made because the target machine
actively refused it (Connection: mysql, ...)
```

`php artisan migrate:status`, every test, and every DB call fail with this.

### Root cause (this machine, 2026-07-29)

The Windows service `mysql` is registered to:

```
C:\xampp\mysql\bin\mysqld.exe --defaults-file=c:\xampp\mysql\bin\my.ini mysql
```

…but `C:\xampp\` **does not exist** on this machine. Laragon's MySQL lives at
`D:\Zyad\laragon\bin\mysql\mysql-8.4.3-winx64\`, but its `lib\plugin` directory
is **empty**, so `mysqld.exe` fails at boot with:

```
mysqld: Can't open shared library '...\lib\plugin\component_reference_cache.dll'
(errno: 126 The specified module could not be found.)
```

**Both** paths are broken. The service is set to `Automatic` so it tries to start
on every boot and silently fails.

### Fix (you need an admin shell)

**Option A — re-point the service to the working Laragon MySQL (preferred):**

```powershell
# Run PowerShell as Administrator
$svc = Get-WmiObject -Class Win32_Service -Filter "Name='mysql'"
# Use sc.exe to change the binary path
sc.exe config mysql binPath= '"D:\Zyad\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe" --defaults-file="D:\Zyad\laragon\bin\mysql\mysql-8.4.3-winx64\my.ini" mysql'
Start-Service mysql
Get-Service mysql   # should be Running
```

Then create the `pharmacy_db` database (the data dir already has a `pharmacy_db`
folder, so this should auto-detect; otherwise `CREATE DATABASE pharmacy_db;` as root).

**Option B — repair the Laragon MySQL plugin dir:**

The `lib\plugin` directory is empty. Re-extract the plugin DLLs from the original
MySQL 8.4.3 zip, or run the official MySQL Installer's "Repair" option against
`D:\Zyad\laragon\bin\mysql\mysql-8.4.3-winx64\`.

**Option C — install a fresh MySQL via Laragon's UI:**

Open Laragon → MySQL → Version → pick 8.x → Start. Laragon will create a clean
data dir, register the service correctly, and start it.

### After the fix

```powershell
# Sanity check
php artisan migrate:status
php artisan test
```

Expected: all green.

---

## 3. Common Commands

| Goal | Command |
|------|---------|
| Run dev server | `php artisan serve` |
| Run all tests | `php artisan test` |
| Run one test | `php artisan test --filter=FeatureStatusTest` |
| Routes list | `php artisan route:list` |
| Routes JSON | `php artisan route:list --json` |
| Fresh DB + seed | `php artisan migrate:fresh --seed` |
| Backup DB | `php artisan backup:run` (see `app/Console/Commands/`) |
| Tinker | `php artisan tinker` |

---

## 4. Architecture Cheat-Sheet

- **Backend:** Laravel 10.48.28 on PHP 8.3
- **Modules:** `Modules/*` (Laravel-Modules by nWidart)
- **API base:** `/api/v1/*` (Laravel Sanctum bearer auth)
- **Web admin:** `/admin/*`
- **Mobile:** `pharmacy-store-app-codecanyon-main/` (Flutter)
- **Multi-tenant:** `business_id` column on most tables, resolved by
  `App\Services\TenantResolver`

### Domain modules (currently in code)

| Module | Status | Where |
|--------|--------|-------|
| Auth + Users + Roles | ✅ | `app/Http/Controllers/Api/Auth/` |
| Business (multi-tenant) | ✅ | `app/Models/Business.php` |
| Products + Categories | ✅ | `app/Models/Product.php` |
| Stock + FEFO | ✅ | `app/Services/FefoService.php` |
| Purchases | ✅ | `app/Models/Purchase.php` |
| Sales + Invoices | ✅ | `app/Models/Sale.php` |
| Prescriptions | ✅ | `app/Models/Prescription.php` |
| Drug Interactions | ✅ | `app/Models/DrugInteraction.php` |
| Sales Prediction (AI) | ✅ | `app/Services/PredictionService.php` |
| Auto-Order | ✅ | `app/Services/AutoOrderService.php` |
| Inventory Turnover | ✅ | `app/Services/InventoryTurnoverService.php` |
| **Stock Audit & Reconciliation** | ✅ | `app/Services/StockAuditService.php` |
| **Financial Audit** | ✅ | `app/Services/FinancialAuditService.php` |
| Insurance | ❌ | (not started — see §5) |
| Multi-Warehouse | ❌ | (not started) |
| Drug Recall / Traceability | ❌ | (not started) |
| Loyalty / CRM | ⚠️ | (basic only) |

> **Note:** Stock Audit, Financial Audit, and Inventory Turnover were completed
> on 2026-07-27/28 but the `GAP_ANALYSIS.md` was not refreshed to reflect this.
> Always trust the code over the gap doc.

---

## 5. Where the Real Gaps Are (post 2026-07-29 audit)

The original `GAP_ANALYSIS.md` is stale. Current honest assessment:

1. **Insurance** — DB + Models + Service + Controller + API needed.
2. **Multi-Warehouse** — Needs new schema (`warehouses`, `warehouse_stocks`,
   `stock_transfers`) and changes to `Stock` model.
3. **Drug Recall / Traceability** — Needs `batch_serial_numbers`, `recall_events`
   tables and supplier→customer lot tracking.
4. **Insurance claims** — even if insurance companies exist, claims flow is missing.
5. **Loyalty / CRM** — only basic customer record exists.
6. **Receipts printing** — many payment providers configured, no print template.

The `GAP_ANALYSIS.md` document has been updated to reflect actual current state.

---

## 6. CI / Quality Gates

Already in place:

- `.github/workflows/ci.yml` — CI on push
- `.github/workflows/phpunit.yml` — PHPUnit
- `.github/workflows/secret-scan.yml` — gitleaks
- `.github/dependabot.yml` — auto dependency PRs

Local pre-commit checklist:

```bash
composer install
php artisan test
./vendor/bin/pint   # code style
```

---

## 7. Deployment (Render + Supabase)

The Laravel app deploys from `render.yaml` — import it as a Render **Blueprint**
(New + → Blueprint → this repository). `vercel.json` is *not* this app: it builds
the separate Vite/Firebase SPA.

### What gets created

| Service | Type | Role |
|---|---|---|
| `z-syst-api` | web (Docker) | nginx + php-fpm under supervisord |
| `z-syst-scheduler` | cron (Docker) | `php artisan schedule:run`, every minute |

Both build from the same `Dockerfile`. `APP_ROLE` decides the behaviour:
`docker/entrypoint.sh` execs `schedule:run` for `cron` and supervisord for `web`.

The scheduler is mandatory, not optional — `app/Console/Kernel.php` schedules ten
commands (`expiry-alert:send`, `backup:database`, `doctor-attention:calculate`,
`subscriptions:manage`, `forecast:generate`, `anomalies:scan`, `telegram:daily`,
`patients:refill`, `eta:retry`, and a `queue:work --stop-when-empty` runner). None
of them run without it. There is deliberately **no queue-worker service**: the
code dispatches zero jobs today, and the scheduler already drains the queue.
(The root `supervisor.conf` is a bare-metal VPS artefact for a redis worker at
`/var/www/z-syst`; it is not this container's process manager.)

### Secrets to fill in the dashboard

Keys marked `sync: false` in `render.yaml` are prompted for and never written
back into the repository: `APP_KEY`, `DB_PASSWORD`, `SUPABASE_ANON_KEY`,
`SUPABASE_JWT_SECRET`, the `MAIL_*` credentials, the `AWS_*` credentials,
`HCAPTCHA_SITE_KEY`, `REDIS_PASSWORD`. Generate `APP_KEY` with
`php artisan key:generate --show` and store the whole `base64:...` value;
`docker/deploy.sh` aborts the deploy if it is empty.

`SUPABASE_FALLBACK_ELOQUENT` must stay `false`: GoTrue owns credentials and
`public.app_users` is a per-business profile mirror with no `email`/`password`.

### What runs where — and why the split matters

Render runs `preDeployCommand` in a **separate one-off container**. Only the
mounted disk (`/var/www/html/storage`) survives from it. So:

- `docker/deploy.sh` (preDeploy) → `migrate --force --isolated` only. The
  database is external, so its effects persist.
- `docker/entrypoint.sh` (every boot) → storage skeleton, permissions,
  `package:discover`, `config:cache`, and for the web role nginx config,
  `view:cache` and `storage:link`. These write to `bootstrap/cache` and
  `public/`, i.e. inside the container, so building them in preDeploy would
  produce caches the serving container never sees.

### Three traps that are already handled — do not "simplify" them away

1. **`php artisan route:cache` must never be run.** `routes/web.php` registers
   closure routes (e.g. `Route::get('/dashboard', fn () => redirect()...)`), and
   Artisan refuses to serialize closures — the command aborts and would fail
   every deploy. Converting those closures to controllers is a separate change.
2. **`clear_env = no` and `variables_order = "EGPCS"` are load-bearing.**
   php-fpm's documented default strips the environment from workers, which would
   mean no `DB_PASSWORD` and no `APP_KEY` at runtime. This codebase also calls
   `env()` directly in **451 places outside `config/`** (including
   `resources/views/admin/settings/system.blade.php`), so environment visibility
   cannot be assumed. Both settings are explicit — in
   `docker/php-fpm-pool.conf` and `docker/php.ini` — rather than inherited from
   whatever the base image happens to ship.
3. **`config:cache` is safe only because configuration comes from real
   environment variables.** Render injects them, and `env()` reads `$_SERVER` /
   `$_ENV`. If a deployment ever switches to a `.env` *file*, caching config
   would silently turn those 451 `env()` calls into `null`.

### Migrations and the production ledger

Production was **not** built by `php artisan migrate`: `public.migrations` did not
exist at all. On 2026-11-10 the schema was aligned to the repository (Supabase
migrations `align_prod_with_repository_migrations` and `fix_banners_imageurl_case`)
and the ledger was baselined (`baseline_laravel_migrations_ledger`), which records
all 116 migrations as applied. Without that baseline the first `migrate --force`
would have re-run every migration and failed on the first non-idempotent
`Schema::create`.

`tests/Feature/SchemaDriftTest.php` (blocking CI job `schema-drift`) keeps models,
migrations and production locked together. Refresh
`database/schema/production_tables.txt` after any production schema change — the
query is in that file's header.

### Health check

`healthCheckPath: /api/up` → the closure route at the top of `routes/api.php`. It
intentionally does **not** touch the database: a Supabase pooler blip must not
make Render restart the API in a loop.

### Where state lives

Sessions, cache and queue are stored in **Postgres**, not on the container
filesystem:

```
SESSION_DRIVER=database     CACHE_DRIVER=database     QUEUE_CONNECTION=database
```

This was chosen over Redis/Valkey because it needs **no new dependency and no
schema change** — production already had every table Laravel's drivers expect,
with the right shape and the right indexes:

| Driver | Tables | Indexes verified in production |
|---|---|---|
| session | `sessions` | `sessions_pkey`, `sessions_last_activity_index` (drives GC), `sessions_user_id_index` |
| cache | `cache`, `cache_locks` | `cache_pkey`, `cache_locks_pkey` |
| queue | `jobs`, `failed_jobs` | `jobs_queue_index`, `failed_jobs_uuid_key` |

Two consequences worth knowing:

- `php artisan migrate --isolated` now takes a **genuinely shared** lock (a row in
  `public.cache_locks`) instead of a per-filesystem one, so concurrent deploys
  cannot migrate at the same time.
- `config/cache.php` and `config/queue.php` had `'redis'` as their fallback while
  no Redis client existed anywhere in `composer.lock`. An unset env var therefore
  selected a driver that could not connect. Both fallbacks are now `database`, and
  `config/cache.php` states `lock_table` explicitly rather than relying on the
  framework default.

The scheduler in `app/Console/Kernel.php` already runs
`queue:work --stop-when-empty` every minute, so the database queue drains without
a worker service.

### Known trade-off: the persistent disk

`z-syst-api` mounts a 10 GB disk at `/var/www/html/storage`. It used to carry
sessions, cache and queue as well; now it holds exactly two things:

- **uploads** — `FILESYSTEM_DISK=local` writes under `storage/app`, and the app
  writes uploads in 10 places;
- **the application's own log files** (`LOG_CHANNEL=stack`; `config/logging.php`
  has no stdout/stderr channel).

A Render disk is attached to a **single instance**: it pins the service to one
replica and disables zero-downtime deploys. **Do not scale this service
horizontally while the disk is mounted.**

Getting rid of it needs two changes, both of which are code changes rather than
configuration:

- **No S3 adapter.** `league/flysystem-aws-s3-v3` is not in `composer.json`, so
  `FILESYSTEM_DISK=s3` cannot work. `composer require
  league/flysystem-aws-s3-v3` (and a bucket + credentials) must land first. After
  that the disk can shrink to logs only, or disappear once logging is pointed at
  stdout.
- **No Redis client.** `composer.lock` has neither `predis/predis` nor the `redis`
  extension, so `CACHE_DRIVER=redis` still cannot work. The Dockerfile now
  installs `ext-redis`, so the image side is ready; pointing `REDIS_HOST` at a
  Valkey instance and switching the three drivers to `redis` is then
  configuration only. The `redis` driver works unchanged with **Valkey** (free
  drop-in — `docs/valkey-setup-ar.md`, `docker-compose.valkey.yml`). Valkey is
  worth doing for cache and sessions at load, since every session read is
  currently a database round-trip to Supabase through the pooler.

---

## 8. File Layout (where to find what)

```
app/
  Console/Commands/         # artisan commands (backup:run, etc.)
  Exceptions/               # custom domain exceptions + ErrorCode enum
  Http/
    Controllers/Api/        # REST API
    Controllers/Admin/      # web admin panel
    Middleware/             # tenant, throttle, etc.
    Requests/               # form-request validation
    Resources/              # API transformers
  Models/                   # Eloquent models (one per table)
  Services/                 # business logic (no DB queries in controllers)
  Notifications/
Modules/                    # nWidart/laravel-modules sub-apps
  Landing/                  # public-facing landing page
routes/
  api.php                   # /api/v1/*
  web.php                   # web routes
  admin.php                 # /admin/*
  auth.php
database/
  migrations/               # chronological
  seeders/
  factories/
tests/
  Feature/                  # HTTP tests
  Unit/                     # pure logic
```

---

## 9. When Things Break

| Symptom | First check |
|---------|-------------|
| `Connection refused` | §2 (MySQL service) |
| `Class "X" not found` | `composer dump-autoload` |
| `Route [x] not defined` | `php artisan route:clear && php artisan route:list` |
| 419 on POST | CSRF: API uses Sanctum bearer, not session |
| 403 on `/api/*` | `business_id` mismatch; check `TenantResolver` |
| Tests randomly fail | Clear `phpunit.result.cache`; check `DB_DATABASE=pharmacy_testing` |

---

*Maintained by the Z-Syst team. Update this file when you discover a new
recurring issue — future you will thank present you.*
