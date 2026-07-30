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

## 7. Deployment Notes (when ready)

- `APP_DEBUG=false` and a **fresh** `APP_KEY` in production
- Real `DB_PASSWORD` (no empty root)
- Set `CACHE_DRIVER=redis` and `SESSION_DRIVER=redis` for multi-instance
- Set `QUEUE_CONNECTION=redis` (or `sqs`/`beanstalkd`)
- Run `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- Run `php artisan migrate --force` from CI, not from a manual SSH session
- Schedule `php artisan schedule:run` via cron every minute, or use Laravel Cloud

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
