# 18 — Live E2E Run: Full Owner & User Journey (PHP 8.3 WASM)

**Date:** 2026-11-01 · **Branch:** `arena/39415603-z-syst` · **Result: PASS with documented environment caveats**

The application was booted end-to-end (Laravel 10.50.2 on PHP 8.3.33 WASM via
`@php-wasm/node`), migrated, seeded, served over HTTP, and exercised as both an
**owner** (artisan, logs, config, migrations) and a **user** (login, Sanctum
tokens, API, tenant isolation). Every defect hit on the way was fixed in this
branch.

## 1. Findings fixed (real product bugs, verified live)

| # | Finding | Severity | Fix (commit) |
|---|---------|----------|--------------|
| 1 | `app/Http/Controllers/API` vs `Api\` namespace: route:list fatal "Class does not exist" on Linux (works only on case-insensitive filesystems) | high | renamed dir to `Api` (48c3b1a) |
| 2 | Duplicate `audit_logs` migration → `migrate:fresh` fails "table already exists" | high | removed superseded 2026_08_01 file (a3cd4f6) |
| 3 | Duplicate `warehouses` composite index → migration fails | med | dropped re-creation (a3cd4f6) |
| 4 | `branches` table referenced by 10+ `foreignId('branch_id')->constrained()` but never created → sqlite inserts fail ("no such table") | high | new 2026_08_07_000000 migration (a3cd4f6) |
| 5 | `DatabaseSeeder` used MySQL-only `SET FOREIGN_KEY_CHECKS` on every driver | med | driver-aware mysql/sqlite PRAGMA (a3cd4f6) |
| 6 | `PlanSeeder` writes `isActive` column that doesn't exist (`status`) | med | fixed column (a3cd4f6) |
| 7 | `BusinessSeeder` written against a foreign schema (businessName/city/start_date…) | med | rewritten to real schema (a3cd4f6) |
| 8 | **IDOR / tenant isolation breach**: `GET/PUT/DELETE /api/v1/products/{id}` had no `business_id` filter — business B could read/modify/delete business A's records (verified live: cross-tenant GET returned **200 with full data**) | **critical** | `App\Traits\TenantScoped` global scope on Product, Party, Sale, Purchase, Stock, Expense (613149d / 47e6611) |
| 9 | MySQL-only `DATE_FORMAT()` in dashboard/statistics + purchase reports → 500 under sqlite | med | `App\Support\SqlDate::format()` (c777782) |

## 2. Verified test matrix (live HTTP, after fixes)

| Test | Expected | Result |
|------|----------|--------|
| Landing `/` | 200 full page | ✅ 200 |
| `/login` page | 200 | ✅ 200 |
| POST `/api/v1/sign-in` staff (good creds) | 200 + Sanctum token | ✅ |
| POST `/api/v1/sign-in` admin from app | 406 (policy) | ✅ 406 |
| API without token | 401 | ✅ 401 |
| B reads A's product (id=1) | 404 | ✅ 404 |
| B updates/deletes A's product | 404 | ✅ 404 |
| A reads own product | 200 | ✅ 200 |
| A/B product lists | own rows only | ✅ disjoint |
| `/api/v1/dashboard` | 200 + stats | ✅ 200 |
| `migrate:fresh --seed` | all migrate + seed | ✅ (all DONE, 0 FAIL) |
| `migrate:status` | all Ran | ✅ |
| `schedule:list` | 4 cron jobs | ✅ |
| `queue:failed` | empty | ✅ |
| `storage:link` | idempotent | ✅ |
| `doctor-attention:calculate --business-id=1` | scores calculated | ✅ |
| `expiry-alert:send`, `subscriptions:manage` | run clean | ✅ |
| Cross-tenant payloads | never leaked | ✅ |

## 3. Environment caveats (sandbox only, NOT app bugs)

The run happens inside PHP 8.3 WebAssembly (`@php-wasm/node 3.1.54`):

- `proc_open`/`shell_exec`/`exec` must stay disabled (Symfony's `stty` probe
  deep-crashes the WASM runtime) → `artisan about` cannot probe
  git/composer there. Everything else works.
- ext-intl is loaded via `withIntl: true`; a `Normalizer` wrapper is only a
  fallback when intl is absent.
- Certain deep C-stack paths (failed bcrypt verify inside an HTTP request,
  first compile of some Blade views) can abort the asyncify runtime. The
  harness (tools_local/phpw-runner) detects both thrown crashes and
  "zombie" empty-500 responses and reboots the runtime transparently
  (client sees a single 503 "please retry").
- Deep C-stack depth also makes the console kernel boot via an explicit
  per-provider boot loop (reflection) in `artisan`/`file` modes.
- Workspace snapshot pruning (platform artifact caps) repeatedly ate
  `vendor/` inside the repo; the harness therefore runs against a golden
  `/home/user/zvendor` (prod-only, ≤10k files) and an app snapshot
  `/home/user/appsync` refreshed at guardian start. `installed.json` is
  generated with `extra.laravel.providers` so Laravel's package manifest
  rebuild keeps Sanctum/DomPDF/Excel/etc registered.

## 4. Test accounts (seeds)

| Email | Password | Role |
|-------|----------|------|
| superadmin@z-syst.com | SuperAdmin123! | superadmin |
| admin@test.com | Admin123! | admin (blocked from app login by design → 406) |
| staff@test.com | Staff123! | staff, business #1 |
| userb@test.com | UserB123! | staff, business #2 |

## 5. Harness recipe (reproducible)

```
# golden vendor (prod-only; prod-only is now the installer default)
python3 tools_local/vendor_install.py          # → Z-Syst/vendor (~7.9k files)
cp -r Z-Syst/vendor /home/user/zvendor

# app snapshot + serve (guardian refreshes appsync, respawns on crash)
node tools_local/phpw-runner/guardian.mjs      # see phpw/ workspace copies
```

In-repo copies of the harness: `tools_local/phpw-runner/{runner,guardian}.mjs`,
`tools_local/phpw-runner/package.json`, `tools_local/vendor_install.py`.
