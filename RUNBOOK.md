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

### Schema type audit (run after any production DDL)

```bash
python3 tools/audit-schema-types.py    # exit 0 == no unexplained divergence
```

CI builds sqlite *from the migrations*, so a migration that declares the wrong
column type produces a green build and a broken production database. This tool
closes that gap: it parses every `database/migrations/*.php` (up-phase only) and
compares the result, by type **family**, against the recorded live schema in
`database/schema/production_column_types.txt`.

Family-level on purpose. `boolean` in production where the model compares
`'pending'` raises `operator does not exist: boolean = text` at runtime;
`varchar(255)` vs `text` breaks nothing. Reporting both would bury the fault
that matters.

It is **not** wired into CI as a gate. The fixture is a snapshot of production,
so a contributor who legitimately adds a column in a migration *before* applying
it to production would fail the build for doing the right thing. Treat it as the
post-DDL check it is: after changing the live schema, refresh the fixture with
the query in the tool's docstring and re-run it until it reports
`NEW FINDINGS: 0`. Every divergence that is understood rather than fixed is
listed in the tool's `ACCEPTED_*` maps with its reason — add to those instead of
deleting a finding.

`tests/Feature/MigrationTypeContractTest.php` is the part that *is* gated: it
pins the specific type decisions in the migration sources and asserts the columns
the application writes exist after migrating.

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
- `config/session.php` likewise fell back to the framework default `'file'`, which
  on an ephemeral-disk host is exactly the failure this section exists to prevent:
  one missing or misspelled `SESSION_DRIVER` and every deploy silently logs
  everyone out. Its fallback is now `database`, so all three stateful drivers fail
  safe to Postgres. This changes nothing for local development or tests — both pin
  the variable explicitly (`.env.example` → `file`, `phpunit.xml` → `array`).

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


## 10. Security posture (pre-launch review, 2026-11-13)

Everything below is enforced in code, not by convention. The test that keeps each
control honest is named next to it — if a control is removed, its test must be
removed in the same commit, which is what makes the removal visible in review.

### 10.1 Control map

| Control | Where | Verified by |
|---------|-------|-------------|
| Setup/update wizard gone, not just blocked | package removed from `composer.json`/`composer.lock`; published files deleted; `BlockInstallerRoutes` (global stack) + `docker/nginx.conf.template` + `touch storage/installed` kept as depth | `SupplyChainHardeningTest::test_the_setup_wizard_package_is_not_a_dependency`, `::test_no_published_installer_files_remain_in_the_repository`, `::test_the_route_table_contains_no_wizard_prefixes`; `SecurityHardeningTest::test_setup_wizard_routes_are_unreachable` |
| Trusted proxies = private ranges only | `app/Http/Middleware/TrustProxies.php`, override with `TRUSTED_PROXIES` | `SecurityHardeningTest::test_trusted_proxies_*` |
| Guest auth endpoints are rate limited | `app/Providers/RouteServiceProvider.php` (`auth-guest`, `auth-reset`) attached in `routes/auth.php`; `/login` limits itself in `LoginRequest` | `SecurityHardeningTest::test_the_guest_auth_endpoints_carry_a_throttle`, `::test_the_password_reset_request_endpoint_actually_limits` |
| Generated URLs are https in production | `app/Providers/AppServiceProvider.php` (`URL::forceScheme`) | manual: `route()` output on Render |
| Payment price computed server-side | `app/Http/Controllers/Admin/GatewayPaymentController.php` (client `amount` ignored, read from `subscription_plans.price`) | `SecurityHardeningTest::test_gateway_allowlist_is_closed` covers the gateway half |
| Gateway callback hardening | `routes/web.php` (`where` + `throttle:60,1`), `GatewayWebhookController` (allowlist), `PaymentGatewayService::mode()` (unknown ⇒ `disabled`) | `SecurityHardeningTest::test_callback_for_unknown_gateway_is_not_found` |
| A valid signature cannot buy an entitlement it does not cover | `PaymentGatewayService::handleCallback()` — reference must match and is never overwritten; gateways outside `OUTCOME_BOUND_GATEWAYS` (fawry, instapay) stay `pending`; `success` defaults to false | `OperationsTest::test_replaying_a_declined_fawry_callback_with_success_true_grants_nothing`, `::test_paymob_live_callback_is_paid_because_its_hmac_covers_the_outcome`, `SecurityHardeningTest::test_only_outcome_bound_gateways_are_trusted_with_the_result` |
| Upload allowlist (extension **and** sniffed MIME) | `config/integrations.php` → `integrations.uploads`, enforced by `app/Helpers/HasUploader.php::zsValidateUpload()` | manual: an `.svg` upload is rejected |
| Uploaded files never behave as documents | `docker/nginx.conf.template` (`location ^~ /uploads/`: nosniff + `default-src 'none'; sandbox`, `Content-Disposition: attachment` for svg) | manual: open an uploaded svg directly |
| Admin denials are visible to API callers | `app/Http/Middleware/AdminMiddleware.php` (403 JSON, redirect only for browsers) | `SecurityHardeningTest::test_admin_panel_denies_json_callers_with_403_instead_of_a_redirect` |
| `staff` confined to operational areas | `app/Http/Middleware/AdminMiddleware.php` — three tiers; staff is deny-by-default, passes a route that carries its own `permission:`/`role:` gate, or is in `STAFF_OPERATIONAL` (see §10.10) | `SecurityHardeningTest::test_staff_is_denied_the_administrative_back_office`, `::test_staff_keeps_the_till_and_the_operational_screens`, `::test_routes_with_their_own_gate_are_left_to_that_gate`, `::test_the_staff_allowlist_has_no_dead_entries`, `::test_tenant_owners_and_platform_admins_still_reach_the_admin_panel` |
| No middleware rewrites request input | `SecurityCheck` deleted; `SecurityService::escapeString()` (addslashes) deleted | `SupplyChainHardeningTest::test_the_request_mutating_security_middleware_is_gone`, `::test_the_addslashes_helper_is_gone` |
| User uploads are not in Git | `.gitignore` (`/public/uploads`, `/public/demo_images`), 202 files untracked | `SupplyChainHardeningTest::test_user_uploads_are_not_tracked` |
| Dependency floors + a real build gate | `package.json` (`engines.node`, `overrides`), `composer.lock`, CI job `frontend` | `SupplyChainHardeningTest::test_locked_*`, `::test_the_grpc_override_survives_and_the_lock_honours_it`, `::test_the_node_runtime_is_pinned`; CI `frontend` |
| No REPL in the production image | `laravel/tinker` moved to `require-dev` (dropped by `dump-autoload --no-dev`) | `SupplyChainHardeningTest::test_tinker_is_a_dev_dependency_only` |
| Failed logins are audited | `app/Http/Requests/Auth/LoginRequest.php`, `app/Http/Controllers/Api/Auth/AuthController.php` → `AuditService::logFailedLogin()` (identifier only, never the password) | `audit_logs` rows with `action='failed_login'` |
| Password-reset code lifetime | `ZSystForgotPasswordController::sendResetCode()` — 30 min, single use, tokens revoked after change | manual |
| Marketing-site headers + strict CSP | `vercel.json` | Vercel deployment headers |
| Full-history secret scan | `.github/workflows/secret-scan.yml` job `gitleaks-history` (`fetch-depth: 0`, `--log-opts=--all`) + `.gitleaks.toml` | the job summary on every push/PR |

### 10.2 Why the wizard was removed, and why three blocks stay

`safiull/laravel-installer` **was** in `composer.json`. It is not any more — it
was removed outright on 2026-11-13 together with the 54 files it had published
into the repository (`config/installer.php`, `resources/views/vendor/installer/`,
`public/installer/`, `public/assets/js/installer.js`). What follows is why the
removal was the right call and not just a config toggle.

The package registered its routes
unconditionally from its own service provider. Its only guard was
`file_exists(storage_path('installed'))`, and that file is git-ignored — so a
container booting on a fresh disk exposed:

* `POST /install/environment/saveClassic` — rewrites `.env` (`APP_KEY`, `DB_*`,
  `APP_DEBUG`). A rewritten `APP_KEY` invalidates every session; a rewritten
  `DB_*` points the application at someone else's database.
* `GET /install/database`, `GET /install/final` — migrations against the live DB.
* `GET /update/final` — deliberately **outside** the guard, per the package's own
  comment in `src/Routes/web.php`; runs `migrate --force`, module seeds and clears
  every cache.

Three independent layers still refuse those prefixes (framework middleware,
nginx, and the marker file) even though the package is gone. They cost one
string comparison per request and they make the failure mode explicit: if the
package is ever re-added — by a `composer require`, by a template update, by a
merge from a branch that predates this one — the routes stay dark instead of
coming back live. `SupplyChainHardeningTest` is what turns that from hope into a
red build.

The removal also drops `Auth::extend('sessionss', …)`, a second session guard
the package registered from its own provider, and the Envy/`CheckToken` purchase
code verification path that phoned home to Envato on every install check.

### 10.3 Uploads

Two layers disagree on purpose, and the stricter one wins:

1. Controller validation (`'image' => 'nullable|image|mimes:jpeg,png,jpg,gif'`) —
   produces the 422 the client sees. `svg` was removed from these rules in this
   review; it used to be allowed here while being rejected at runtime.
2. `HasUploader::zsValidateUpload()` — checks the extension **and**
   `$file->getMimeType()` (finfo content sniffing, not the client's header)
   against `config('integrations.uploads.allowed_*')`. Defaults exclude svg.

Files go cloud-first to the Supabase Storage `uploads` bucket when
`UPLOADS_CLOUD_ENABLED=true`, with the local disk as fallback. Note that the
`public` disk in `config/filesystems.php` has `'root' => '.'`, i.e. the project
root — local fallback therefore writes **outside** `public/` and is not directly
served, which is the safe direction for item 18 but means a local-only upload is
not visible at `/uploads/...` unless the cloud leg succeeded. Keep
`UPLOADS_CLOUD_ENABLED=true` in production.

nginx still hardens `/uploads/`.

`public/uploads/` (109 files, 19 MB) and `public/demo_images/` (93 files) were
committed before that split and are **no longer tracked** as of 2026-11-13 —
this repository is public, and uploads can contain prescriptions and other
tenant/patient data. The files stay on disk; only Git stops carrying them.

Before untracking, every text/varchar/json/jsonb column of every `public` table
in production was scanned for a reference to either path (`%uploads/%`,
`%demo_images%`): **zero rows**, across the 23 image/attachment columns
(`app_users.image`, `banners."imageUrl"`, `products.images`,
`supplier_*`.{file_path,file_name,file_mime_type}, `testimonials.client_image`,
…) and across all 59 json/jsonb columns. No Blade view or built asset references
either directory. So nothing served from Git history was live. Re-run that check
before ever re-tracking them:

```sql
select count(*) from <table> where <column>::text like '%uploads/%'
                            or <column>::text like '%demo_images%';
```

### 10.4 Payments

* `PaymentGatewayService::mode()` returns `disabled` in production unless a
  platform key exists — the `simulation` shortcut (which skips signature
  verification) can never be reached in production.
* `verifySignature()` fails closed when no secret is configured and uses
  `hash_equals()` for both schemes.
* The amount is taken from the plan, never from the request. The webhook proves
  the payer paid *that transaction*; only the server knows what it should cost.
* `StorefrontController::checkout()` already computed totals from
  `products.sales_price` — unchanged, and the reference implementation.

**A valid signature is not the same as a paid invoice.** The two schemes are not
equivalent, and treating them as if they were is what made this the highest-value
payment finding in the review:

| Gateway | Signature | Covers `success`? |
|---------|-----------|-------------------|
| paymob | HMAC-SHA512 over `PAYMOB_HMAC_FIELDS`, which includes `success` | **yes** |
| fawry / instapay | `sha256(merchantCode + reference + amount + secret)` | **no** |

Every input to the fawry/instapay hash is a value the application already holds.
So the signature is *identical* whatever the callback says about the outcome: it
proves "the caller knows the secret and means this transaction for this amount",
and nothing about whether the customer paid. `handleCallback()` then read
`filter_var($params['success'] ?? 'true', …)` — trusting an unsigned field that
**defaulted to true**. One observed callback for a *declined* payment could be
replayed at the same URL with `success=true` and the subscription activated for
free. This cannot be fixed by verifying harder: the outcome is not in the signed
material, and the scheme is the gateway's to define, not ours.

What `handleCallback()` does now:

1. **Reference binding.** The callback's `reference` must equal the stored one;
   a mismatch fails the transaction with reason `reference_mismatch`. The stored
   reference is never overwritten from the callback — it is an input to the
   signature, so letting the callback redefine it would corrupt every later
   verification.
2. **Outcome binding** (`PaymentGatewayService::signatureCoversOutcome()`,
   `OUTCOME_BOUND_GATEWAYS = ['paymob']`). In live mode, a gateway whose
   signature does not cover the outcome does **not** get its `success` flag
   believed: the transaction is left `pending` with reason
   `outcome_not_bound_to_signature` and a `Log::warning`, and no entitlement is
   granted. `pending` rather than `failed` on purpose — the money may genuinely
   have been taken, and recording a decline invites a second charge.
3. **`success` defaults to false**, not true, for the gateways that are trusted.
   An absent or stripped flag used to mean "paid".
4. Simulation is exempt from (2) and only from (2): `mode()` cannot return
   `simulation` in production, and a simulation that could not state its own
   outcome would be useless.

**Consequence for anyone integrating fawry or instapay for real:** as of this
review neither has an API client, credentials, or a `config/services.php` entry,
so both are `disabled` in production and this path is latent rather than live.
Turning one on is not enough — `mode()` will report `live` as soon as a platform
`api_key` exists, and every callback will then sit in `pending` until the
integration adds a **server-to-server status query** (or the gateway offers a
signature that covers the outcome). That is the intended behaviour: it makes the
missing corroboration loud at integration time instead of quiet at attack time.
Wire the query into the `outcome_not_bound_to_signature` branch.

Guards: `OperationsTest::test_replaying_a_declined_fawry_callback_with_success_true_grants_nothing`
(the replay), `::test_paymob_live_callback_is_paid_because_its_hmac_covers_the_outcome`
(the counter-example, so fail-closed is per gateway and not a blanket refusal),
`::test_gateway_rejects_unsigned_callback_in_live_mode` (forged signature, and
the reference rules), and
`SecurityHardeningTest::test_only_outcome_bound_gateways_are_trusted_with_the_result`
(the map itself, including that `success` is still in `PAYMOB_HMAC_FIELDS`).

### 10.5 Rate limiting and client IPs

`throttle:10,1` on `/api/v1/sign-in` and `RateLimiter` (5 attempts) on the web
login are per-IP. That is only true if `$request->ip()` is the client, which
needs the platform proxy to be trusted — see `TrustProxies`. Before this review
`$proxies` was null: every request appeared to come from the proxy, so all users
shared one bucket (one attacker could lock out the whole platform) and
`audit_logs.ip_address` recorded the proxy. `'*'` would have fixed that by
letting clients forge `X-Forwarded-For`. Private ranges only is the setting that
does both.

If Render's router ever connects from a public range, set `TRUSTED_PROXIES`
(comma separated, `REMOTE_ADDR` means "trust the peer") rather than widening the
default in code.

**Coverage of the web authentication routes.** Every state-changing guest
endpoint is now limited; the limits are defined in `RouteServiceProvider::boot()`
and attached in `routes/auth.php`:

| Endpoint | Limiter | Buckets |
|----------|---------|---------|
| `POST /login` | *none at the route level* | `LoginRequest::ensureIsNotRateLimited()` — 5 attempts keyed `email\|ip`, hit on every failure, cleared on success |
| `POST /register` | `auth-guest` | 5/min per `email\|ip` **and** 20/min per IP |
| `POST /otp-resend`, `POST /otp-submit` | `auth-guest` | same |
| `POST /forgot-password` | `auth-guest` | same |
| `POST /reset-password` | `auth-reset` | 10/min per IP (it carries a GoTrue recovery token, not an address) |

`POST /login` deliberately has no route-level throttle: it is already limited
more tightly and with a better key inside the form request, and a second counter
over it would only make the two disagree.

The reason the other five needed limits is not password guessing — it is that
each one does real work per request. `forgot-password` and `reset-password` call
Supabase GoTrue **outbound**, so an unthrottled guest could burn the project's
auth quota (a cheap denial of service on the reset path for every legitimate
user) and fire unlimited recovery e-mails at any address, which is harassment.
`register` returns 403 in production, but under `supabase.fallback_eloquent`
(dev/CI) it provisions an account and runs bcrypt, which is deliberately
expensive. `otp-resend` and `otp-submit` are 410 stubs today; they are limited
anyway so the bucket is already in place if they are ever implemented.

Two buckets rather than one, because either alone is bypassable: keyed on
`email|ip` an attacker rotating addresses still pays per address, and keyed on IP
alone one attacker can exhaust a whole pharmacy's bucket behind a shared NAT.
Both together cap each.

GET routes are not throttled — they render a form and cost nothing, so limiting
them would mostly risk locking out a whole site behind one address for no gain.

Guards: `SecurityHardeningTest::test_the_guest_auth_endpoints_carry_a_throttle`
(the wiring) and `::test_the_password_reset_request_endpoint_actually_limits`
(drives the real endpoint five times through and asserts the sixth is refused —
declaring a limiter and attaching it are two different things).

### 10.6 Credential rotation checklist (from the full-history scan)

The scan over all 241 commits of `main` found files that were committed and later
deleted. **Deleting a file does not remove it from history** — every value below
must be treated as public and rotated in the provider's own dashboard. Nothing
here is a value; names only, because this file is in a public repository.

| What | Where it was | Where to revoke / rotate |
|------|--------------|--------------------------|
| Laravel `APP_KEY` (`base64:` + 44 chars — a real key, 2 versions) | `.env` (Jul–Aug 2026, commits `90b942be`…`a7eca878`) | Render → `z-syst-api` → Environment → `APP_KEY`: set a fresh `php artisan key:generate` value. Rotating invalidates all sessions/cookies and anything encrypted with `Crypt` — re-issue Sanctum tokens afterwards. |
| SMTP credentials (`MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_HOST`) | `.env` | The mail provider's dashboard (API key / app password), then update `MAIL_*` on Render. |
| Pusher `PUSHER_APP_ID` / `PUSHER_APP_SECRET` | `.env`, `.env.production` | Pusher → App → App keys → reset secret. Verify first whether these were placeholders. |
| Google **service-account private key** (full JSON with a PEM block) | `Super-Admin/public/uploads/service-account-credentials.json`, project id `pospro-7a386` | Google Cloud Console → project `pospro-7a386` → IAM & Admin → Service Accounts → Keys → delete the key. If that project is not yours, it shipped inside the purchased POS Pro template — in that case the only action is to keep it out of this repository. |
| Firebase iOS config (`API_KEY`, `CLIENT_ID`, `DATABASE_URL`, project `salespro-saas-4a6d5`) | `third_party/pospro-app-codecanyon-Codecanyon_6.1/ios/Runner/GoogleService-Info.plist` | Firebase console → project settings → API keys → restrict by bundle id / HTTP referrer. These are client identifiers, not secrets, so restriction (not rotation) is the fix. |
| Local Postgres user/password | `docker-compose.yml` | Dev-only; rotate if that compose file was ever pointed at a shared database. |
| `DB_PASSWORD`, `AWS_ACCESS_KEY_ID`, `AWS_BUCKET`, `SUPABASE_ANON_KEY`, `SUPABASE_JWT_SECRET`, `APP_KEY` in `.env.production.example` | tracked in HEAD today | Inspected value-by-value: all are placeholders (15 uppercase / 0 digits, `AWS_ACCESS_KEY_ID_HERE`-shaped, etc.). `DB_HOST`, `DB_USERNAME`, `DB_DATABASE` are real but are not secrets. **Re-check this row if the file is ever edited** — a real value pasted into it is published immediately, because this repository is public. |

Two structural notes that follow from the above:

* This repository is **public** (`zyadgfh/Z-Syst`). Anything committed here is
  public forever, including deleted files reachable by SHA.
* Purging history (`git filter-repo` / BFG) rewrites every SHA and force-pushes
  `main`, which invalidates open PRs and every local clone. It does **not**
  replace rotation — GitHub caches forks and the old SHAs. Rotate first; decide
  about the rewrite afterwards.

### 10.7 Re-running the history scan locally

```bash
# The clone is shallow by default; history must be fetched once.
git fetch --depth=2147483647 origin refs/heads/main:refs/remotes/origin/main
gitleaks detect --source . --log-opts="--all" --config .gitleaks.toml --redact=24
```

CI runs the same thing as the non-blocking `gitleaks-history` job on every push
and pull request; its findings land in the job summary and in the
`gitleaks-history-report` artifact. It is non-blocking because a historical
finding cannot be fixed by the PR that happens to trigger it — it needs a
rotation.

### 10.8 Supply chain: what is installed, and the traps in changing it

**PHP.** `composer.json` lost `safiull/laravel-installer` (§10.2) and moved
`laravel/tinker` to `require-dev` — a REPL with full application access has no
business being present in the production image, and `Dockerfile` already drops
dev packages with `dump-autoload --optimize --no-dev`. `laravel/framework` was
bumped **v12.69.3 → v12.71.0** in the lock, which closes GHSA-63hm-g96r-2m2q
(low). The two tags differ *only* in `require-dev`
(`orchestra/testbench-core`, `phpstan/phpstan`); `require`, `replace`,
`conflict` and `provide` are byte-identical, so the patch carries no new runtime
constraint. Result: **91 production packages (was 95), 42 dev (was 39)**, and no
open advisory in the PHP graph.

**Trap — the lock was edited by hand, so its `content-hash` had to be recomputed.**
`composer validate --strict` (CI, `php-quality`) compares the hash in
`composer.lock` against a hash of the *relevant keys* of `composer.json`
(`name, version, require, require-dev, conflict, replace, provide,
minimum-stability, prefer-stable, repositories, extra`, plus `config.platform`
only when set, `ksort`ed, then `md5` of `json_encode` with **no whitespace and
`/` escaped** — `JsonFile::encode($data, 0)`). Any other encoding gives a
different hash and CI fails with "lock file is not up to date". If you have a
local PHP/composer, just run `composer update --lock` and skip all of this.

**Trap — dropping a package changes the dependency closure, and the closure rules
are subtle.** `illuminate/*` are satisfied by `laravel/framework`'s `replace`,
and virtual packages are `php`, `php-*` (**including `php-64bit`**), `ext-*`,
`lib-*`, `hhvm*`, `composer-runtime-api`, `composer-plugin-api`. Getting
`replace` wrong silently drops `phpoffice/phpspreadsheet`, `markbaker/*`,
`composer/semver` and `ezyang/htmlpurifier` — i.e. the whole Excel chain.

**npm.** `vite` **^3 → ^7.3.8** and `laravel-vite-plugin` **^0.7 → ^2.0.1**
(resolved 7.3.8 / 2.1.0, esbuild 0.15.18 → 0.28.2). That clears the high
`vite <=6.4.3` finding and the moderate `esbuild <=0.24.2` one
(GHSA-67mh-4wv8-2f99 — the dev server answers cross-origin requests and leaks
what it reads). Both are dev-server issues, but a developer's `npm run dev` is
where they land. `@vercel/analytics` and `@dataconnect/generated` were dropped:
neither is imported anywhere. `engines.node` was added (`>=22.12.0`) because
vite 7 requires `^20.19.0 || >=22.12.0` and Vercel otherwise picks its own
default — a default change becomes a build failure with no commit to explain it.

**Trap — `overrides` looked like leftovers and was load-bearing.**
`"@grpc/grpc-js": ">=1.13.6"` reads as if it existed for
`@dataconnect/generated`. It does not: `@firebase/firestore` nests
`@grpc/grpc-js <=1.13.5`, which carries two **high** advisories
(GHSA-m9gg-hp2v-232j, GHSA-f596-whhp-79r4), and Firebase publishes no release
that bumps it. Removing the override re-exposed 4 high findings; `npm audit fix
--force` "resolves" them by **downgrading firebase to 9.14.0**. The override
stays, and `SupplyChainHardeningTest::test_the_grpc_override_survives_and_the_lock_honours_it`
fails the build if anyone deletes it again.

**Trap — `firebase` is *not* unused.** It is easy to conclude otherwise, because
nothing in the SPA imports it. `resources/js/app.js` → `resources/js/firebase.js`
imports `firebase/app` and `firebase/firestore`, and that file is an input of
`vite.config.js`. See the open decision below before removing it.

**npm has no Dependabot coverage** (`.github/dependabot.yml` lists composer and
pub only). The CI job `frontend` is the substitute: it runs `npm ci` — which is
the check that `package-lock.json` and `package.json` still agree — then builds
**both** vite configs, then reports `npm audit` as a warning. The audit is a
warning for the same reason `composer-audit` is: an advisory in a transitive
dependency is not always fixable by the PR that discovers it. The version floors
this project *can* control are asserted as blocking PHPUnit tests instead.

**Open decision — the Laravel-side frontend bundle appears to be dead.** No
Blade view calls `@vite` or `vite()`, `resources/css/app.css` is **0 bytes**,
`public/build` is git-ignored, and `Dockerfile` has no node/npm step at all — so
the image never contains the bundle even if it were referenced. If that reading
is right, `build:laravel`, `vite.config.js`, `laravel-vite-plugin`,
`resources/js/*`, `resources/css/app.css` and the npm packages `firebase`,
`tailwindcss`, `postcss`, `autoprefixer`, `@tailwindcss/forms`, `alpinejs`,
`axios` and `lodash` are all dead weight (Alpine is loaded from a CDN in the
Blade layouts, not from npm). Deleting them removes tracked files, so it is a
product decision, not a security fix — it is deliberately *not* done here.

**Open decision — `dataconnect/` and `src/dataconnect-generated/`.** These are
the Firebase Data Connect **quickstart sample** (`createMovie`, `listUsers`,
`addReview`) generated into the repo. Nothing outside that directory imports
them. The npm dependency is gone; the 9 generated files and the `dataconnect/`
example connector are still tracked.

**Deployment note (found while verifying the above).** The Vercel project
`z-syst` has two production deployments and **both failed**: the latest with
`git_info_fail` (GitHub App installation 404 — the repo moved from `zyadgam` to
`zyadgfh`, and `githubRepoId` changed), the earlier one with
`STATIC_BUILD_NO_OUT_DIR`. Its only domain is `z-syst.vercel.app`; there is no
custom domain, so `app.z-syst.com` (the value in `CORS_ALLOWED_ORIGINS`) is not
served by Vercel. The hardened headers and CSP in `vercel.json` therefore protect
a site that is not currently deployed. Reconnect the GitHub integration in the
Vercel project settings before relying on any of it.

### 10.9 Middleware that was removed on purpose

`SecurityCheck` sat in the `web` group and did this to **every** request input:

```php
foreach ($request->all() as $k => $v) {
    if (is_string($v)) { $request->merge([$k => $this->security->sanitizeHTML($v)]); }
}
```

Three problems, in ascending order of seriousness:

1. It is a regex blacklist. Blacklists miss encodings they were not written for,
   so they never actually stop an attacker who reads them.
2. It **mutates** the request. A product description containing the word
   `select`, a clinical note containing `<3`, a JSON field with a `<` in it was
   silently truncated on the way to the database. That is data corruption in a
   pharmacy system, wearing a security costume — and it is invisible, because
   nothing logs what it rewrote.
3. Its security value was already zero: it only *logged* SQL-injection patterns
   and its `abort(403)` was commented out.

Removed by decision on 2026-11-13 (`app/Http/Middleware/SecurityCheck.php`
deleted, its entry dropped from `App\Http\Kernel::$middlewareGroups['web']`).
What actually protects the application, and what to point at if anyone proposes
putting it back:

| Threat | Real defence |
|--------|--------------|
| SQL injection | Eloquent's builder and bound parameters. Every `whereRaw`/`selectRaw` in the tree passes `?`; the five raw-SQL sites were audited individually. |
| Stored/reflected XSS | Blade escapes on output by default. The five `{!! !!}` sites either emit server-generated barcode SVG (`barcodes/single`, `barcodes/multiple`, `admin/pos/dose-labels`) or pass through `App\Support\HtmlSanitizer::clean()` (two Landing pages). |
| Upload abuse | Extension allowlist **and** finfo MIME sniffing in `HasUploader::zsValidateUpload()`, then nginx §10.3 headers. |

Two services were deliberately **kept**:

* `app/Services/SecurityService.php` — `tests/Feature/SecurityTest.php` exercises
  ten of its methods. Its class docblock now states plainly that it is pattern
  detection and logging, *not* a defence, and `escapeString()` (which was
  `addslashes()` — not SQL escaping: it does not know the connection charset and
  does not escape identifiers) was deleted so nobody reintroduces it.
* `app/Services/XSSProtectionService.php` — `app/Providers/BladeServiceProvider`
  registers `@cleanInput`, `@sanitizeURL`, `@safeAttribute` and `@stripTags`
  from it. Those are opt-in Blade directives, i.e. the safe direction: a
  template author chooses them, and they do not rewrite request input.

### 10.10 Authorization tiers on /admin

`/admin` is not an administrators-only panel. It is the whole authenticated back
office — 289 named routes, and **the till is inside it**. That single fact is
what makes the obvious fixes wrong.

**Where authorization actually comes from.** Not Spatie's tables: production has
no `roles`, `permissions`, `model_has_roles`, `model_has_permissions` or
`role_has_permissions` table, and `App\Models\AppUser` deliberately does not use
Spatie's `HasRoles` trait (see the comment at `AppUser.php:37`). It comes from
`HasSupabaseRoles` — the single `app_users.role` text column plus the ability map
in `config/supabase.php`, with `user_permissions` holding runtime grants on top.
Spatie's `permission:` middleware still works because `HasSupabaseRoles`
implements the Spatie-compatible `checkPermissionTo()`, and Spatie's
`Gate::before` consults it. So a missing Spatie table is not a bug here; a
missing entry in `config/supabase.php` is.

The map, as of this review:

| Role | Abilities | Notable |
|------|-----------|---------|
| `superadmin` | `*` | wildcard |
| `admin` | 82 | platform administration |
| `staff` | 22 | **includes `sales-create`** |
| `shop-owner` | 14 | **does not include `sales-create`** |

Read the last two rows together: the pharmacy employee is the only role allowed
to ring up a sale. `admin/pos/*` and `admin/dose/*` carry no `permission:` gate
of their own, so `AdminMiddleware` is the till's only door.

**Coverage of the fine-grained layer.** Of 289 named admin routes, **38 carry a
`permission:` gate and 251 carry none**. For those 251 the coarse role check is
the entire authorization decision — which is why `staff` being in the blanket
allowlist meant any employee account could reach `users/delete-all`,
`users/bulk-change-status`, `system-settings`, `gateways`, `roles` and `plans`.

**Both obvious fixes were tried and both were wrong.**

* *Allow staff wholesale* (the state before this review): the 251 ungated routes
  are open to every employee.
* *Deny staff wholesale*: the till switches off for the only role permitted to
  use it. This is not theoretical — it turned 4 POS tests red
  (`OperationsTest`: checkout with a promotion, drug-interaction warning, dose
  label sheet with Code128, dose scan lookup) because `UserFactory`'s default
  role is `staff` and those tests are the till's regression suite.

**What is implemented instead.** Three tiers in `AdminMiddleware`:

1. `shop-owner`, `superadmin`, `admin` → the whole panel (gates still apply).
2. `staff` → **deny by default**, with two ways through:
   * the matched route carries its own `permission:` / `role:` /
     `role_or_permission:` gate → step aside and let that gate decide. This is
     deliberate: it keeps `config/supabase.php` the single source of truth.
     staff holds 8 of the 38 gated permissions (`deliveries-read/update`,
     `sales-create`, `shifts-read/create/update`, `stocktake-read/create`) and is
     refused the other 30 by the gate itself — `stocktake-update` and
     `products-create` stay out of reach without `AdminMiddleware` duplicating
     the map and drifting away from it;
   * otherwise the first URI segment after `/admin/` is in
     `AdminMiddleware::STAFF_OPERATIONAL` (21 entries, each one justified by a
     matching ability in the config map).
3. anything else (`customer`, …) → denied.

Entries are **first URI segments**, so nested areas are reached through their
parent: `device-setup/printers` via `device-setup`, `insurance/claims` via
`insurance`, `profile/two-factor` via `profile`. Listing the child instead is a
dead entry, and `SecurityHardeningTest::test_the_staff_allowlist_has_no_dead_entries`
fails on those — that is how a route rename surfaces instead of quietly leaving
staff locked out of a screen or, worse, leaving a stale name that a new
administrative route could be added under.

**Maintenance rule:** adding an entry to `STAFF_OPERATIONAL` widens what every
staff account in every tenant can do, so it must be justified by an ability in
`config/supabase.php` — not the other way round.

**Open follow-up (not done here).** The durable fix for the 251 ungated routes is
to gate them: add `permission:<ability>` using names that already exist in
`config/supabase.php`, so every role is decided by one map instead of by a
middleware allowlist. That is ~250 route edits and each wrong permission name is
a lockout, so it needs its own review pass rather than riding along with a
security batch. Until then the tier rule above is what contains `staff`, and the
other roles are contained by their position in the map plus the 38 existing
gates.

**Test shape.** The tier boundaries are asserted against the middleware directly
with a synthetic matched route (`SecurityHardeningTest::adminDecision()`), so the
assertions are about the authorization decision and not about whether a
controller happens to have its fixtures in order. The destructive endpoints are
additionally asserted end-to-end over HTTP as JSON, so a denial is a visible 403
and not a redirect a client would follow and misread as success.

---

*Maintained by the Z-Syst team. Update this file when you discover a new
recurring issue — future you will thank present you.*
