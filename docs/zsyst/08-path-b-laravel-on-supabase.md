# 08 — Path B executed: Laravel as a façade over Supabase

**Decision:** «B اولا» — keep Laravel, make Supabase Postgres the only database.
**Date:** 2026-10-31 · **Status:** foundation implemented, lint-verified, not yet run against the live DB (see §6)

---

## 1. What exists now in `zs-repo`

| Layer | File(s) | Notes |
|---|---|---|
| **88 Eloquent models** | `app/Models/*.php` | auto-generated from the *live* Postgres schema (`database/supabase_schema.txt`) |
| Generator | `tools/generate-models.py` | re-run after every migration; deterministic |
| Auth client | `app/Support/Supabase/SupabaseAuthClient.php` | GoTrue password grant / refresh / logout / user |
| User provider | `app/Auth/SupabaseUserProvider.php` | Laravel `UserProvider` backed by GoTrue + `app_users` |
| Guard driver | registered in `app/Providers/AuthServiceProvider.php` (`Auth::provider('supabase', …)`) | selected in `config/auth.php` |
| RLS enforcement | `app/Http/Middleware/SetSupabaseContext.php` | `SET LOCAL ROLE authenticated/anon` + JWT claims per request |
| RBAC shim | `app/Models/Concerns/HasSupabaseRoles.php` | `hasRole / hasAnyRole / hasPermissionTo / can / getRoleNames …` over `app_users.role` |
| Permission map | `config/supabase.php` → `permissions` | 7 roles; `@can(...)` resolved by `Gate::before` |
| Config | `config/supabase.php`, `config/auth.php`, `.env`, `.env.example` | |

**Verification performed:** `php -l` on **all 88 models + 9 changed/created files → 0 syntax errors**. 201 `belongsTo` relations derived from `*_id` columns and the uuid FKs mapped to `AppUser`.

---

## 2. Why a middleware, and why it matters more than anything else here

Laravel connects with the `postgres` role → **superuser, BYPASSRLS**. Every RLS policy we audited (137 of them, all using `private.current_business_id()`) would be silently ignored, and one pharmacy could read another's sales.

`SetSupabaseContext` therefore wraps each web request in a transaction and executes:

```sql
set local role authenticated;                       -- or anon when unauthenticated
select set_config('request.jwt.claims', '{"sub":"<uuid>","business_id":7,"app_role":"manager"}', true);
select set_config('request.jwt.claim.sub', '<uuid>', true);
select set_config('request.jwt.claim.role', 'authenticated', true);
```

`SET LOCAL` is transaction-scoped → it cannot leak into the next request on a reused PHP-FPM connection. `private.current_business_id()` then resolves `auth.uid()` → `app_users.business_id` and the policies work exactly as they do for PostgREST clients.

> ⚠️ Never set `SUPABASE_ENFORCE_RLS=false` in production. It is the only thing standing between two tenants.

---

## 3. Auth flow (no password hashes in Postgres)

1. `POST /auth/v1/token?grant_type=password` with `apikey` = anon key → GoTrue validates the password.
2. `SupabaseUserProvider::retrieveByCredentials()` stores the session (access + refresh token) and loads `public.app_users` by `auth.users.id`.
3. Rejection paths are explicit and testable: `missing_credentials`, GoTrue error text, `not_provisioned_in_app_users`, `account_disabled`.
4. `validateCredentials()` only confirms the row matches the authenticated uuid — Laravel never sees a hash.

**Gap to close next:** `app_users` has **no `email` column** (email lives in `auth.users`). Any legacy code doing `User::where('email', …)` must switch to a GoTrue lookup. Recommended: add a read-only view `public.app_users_with_email` joined to `auth.users`, or expose email via the session payload. Not done yet — needs approval since it adds a view over the `auth` schema.

---

## 4. RBAC mapping (Spatie → one column)

| Spatie concept | Supabase implementation |
|---|---|
| `model_has_roles` | `app_users.role` (text) |
| `permissions` / `role_has_permissions` | `config/supabase.php` → `permissions[role][]` |
| `$user->hasRole('admin')` | trait method (same signature) |
| `@can('sales.create')` | `Gate::before` in `AuthServiceProvider` (87 Blade call sites) |
| `$user->hasPermissionTo()` | alias to `hasPermission()` |

Roles seeded in config: `superadmin, admin, manager, pharmacist, cashier, accountant, viewer`.

---

## 5. Models — generation rules (so regeneration never surprises you)

- Table name → StudlyCase singular (`sale_items → SaleItem`, `businesses → Business`, `box_sizes → BoxSize`).
- `$fillable` = every column except `id/created_at/updated_at/deleted_at` (explicit, no `$guarded = []`).
- `$casts`: `numeric → decimal:2` for money, `decimal:4` for quantities/counts, `jsonb → array`, `bool → boolean`, `timestamptz → datetime`, `date → date`.
- `SoftDeletes` only where `deleted_at` exists (9 tables).
- `$timestamps=false` where there is no `created_at` (e.g. `purchase_return_details`); `const UPDATED_AT = null` where only `created_at` exists.
- `app_users` → uuid key, `$incrementing = false`, extends `Illuminate\Foundation\Auth\User`.
- `insurance_companies.api_credentials` is `$hidden`.

---

## 6. Not verified (honest status)

| Item | State |
|---|---|
| Runtime connection to Supabase | **BLOCKED** — `SUPABASE_DB_PASSWORD` / `SUPABASE_ANON_KEY` are stored hashed in Vault; the connector never returns secret values |
| Login end-to-end | NOT TESTED — needs the anon key + a provisioned `app_users` row (DB currently has 0 users) |
| RLS enforcement live | NOT TESTED — needs a running request against the DB |
| Legacy `App\Models\User` references | 10+ files still `use App\Models\User` (Console, Exports, Helper, DemoMode, Api\Auth, Payment, Policies). They resolve to nothing in this workspace copy because the original models were never present. **Reconcile when the real repo is restored**: either keep them as thin subclasses of the generated models, or rename call sites to the generated classes |
| Migrations (65 MySQL ones) | absent from this copy; Path B runs against the existing Supabase schema, so Laravel must not own the schema — migrations stay in Supabase (`create_migration`) |

**Minimum to unblock:** set `SUPABASE_ANON_KEY` and `SUPABASE_DB_PASSWORD` in the app environment (not in chat), then:
```bash
php artisan config:clear
php artisan tinker --execute="dump(DB::select('select current_user, count(*) from public.subscription_plans'));"
```
Expected: `postgres` + 6 rows on the raw connection, and tenant-scoped results once the middleware sets the role.

---

## 7. Security fixes landed with this change

- 🔴 **`GET /update` deleted from `routes/web.php`** — was unauthenticated, ran `migrate`/`seed` and touched `vendor/autoload1.php`.
- 🔴 **`CheckToken` middleware removed** from the `web` group + its import (it could delete `vendor/laravel`).
- 🟠 `config/backup.php` → `pg_dump`; `config/queue.php` → `pgsql` default.

---

## 8. Next steps (ordered)

1. **Provision the first tenant** — `businesses` row + `app_users` row tied to an `auth.users` uuid, so login can be tested.
2. **Email bridge** — view over `auth.users` (needs approval) or store email in `app_users`.
3. **pgTAP tenant-isolation tests** — prove cross-tenant read/write fails under the middleware.
4. **Reconcile legacy model references** once the real repo is restored (GitHub still blocked).
5. Realtime for the POS tables + first Storage bucket (avatars/logos).
