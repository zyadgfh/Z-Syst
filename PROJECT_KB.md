# Z-Syst — Persistent Engineering Knowledge Base
**Owner:** Ziad Gamal (zyadgamal796@gmail.com) · **Agent session:** 2026-10-31 · **Status:** Supabase connected, audited, hardened

> Permanent project memory. Chat history is *not* memory. Update after every significant operation.

---

## 1. Account & Integration Connection Status (verified)

| Integration | State | Verification | Capability |
|---|---|---|---|
| **Supabase** | ✅ **connected** | `validate_credential` → `success: true` | orgs, projects, SQL, migrations, Auth, Edge Functions, secrets, health |
| **Vercel** | ✅ connected | `get_authenticated_user` → `zyadgam` | projects, deployments, domains, env vars |
| **GitHub** | ⛔ unsupported | `list_connector_tools("github")` → `unsupported` | none (Arena offers no GitHub connector) |

**Supabase account:** org **`ZyadGamal's Org`** (`crwijxhkoetuectaaqke`) — single org, 2 projects, both on `eu-central-1`.

---

## 2. Supabase Project Inventory

| Project | Ref | PG | Status (start of session) | Content |
|---|---|---|---|---|
| **ZyadGamal's Project** ← Z-Syst | `atvttthrqsybjjwrbiyl` | 17.6.1.127 | `INACTIVE` → **restored, ACTIVE_HEALTHY (db)** | 88 tables, 137 RLS policies, 14 functions, 1 edge function, **0 rows of data** |
| jobscope-research | `mtziqpyvgebthoynalbq` | 17.11.0.003 | `INACTIVE` (left paused) | 2 edge functions (`jobscope-probe`, `research-control`) |

**Identity confirmation:** the snippet *"Atomic Pharmacy Sale Creation with Inventory Checks"* (owner `ZyadGamal`, 2026‑05‑29) lives in project `34170523` = `atvttthrqsybjjwrbiyl` → this **is** the pharmacy/Z‑Syst Supabase project. Its Edge Function is literally named `z-syst-api`.

**Backup / recovery point:** every table is empty (0 auth users, 0 products, 0 sales, 0 app_users), and the project was paused — restoring was safe and no data was touched.

---

## 3. Supabase architecture — `atvttthrqsybjjwrbiyl`

**Scale:** 88 public tables · 253 foreign keys · 370 indexes · 137 RLS policies · 14 functions · pgTAP + pgcrypto + uuid-ossp + supabase_vault installed.

**Multi-tenancy design (this is good engineering):**
- `private.current_business_id()` → `SECURITY DEFINER`, pinned `search_path = public, pg_temp`, resolves `business_id` from `public.app_users WHERE id = auth.uid()`.
- Every tenant table policy uses `business_id = private.current_business_id()` in both `USING` and `WITH CHECK`.
- Trigger functions `enforce_same_business()` / `enforce_child_same_business()` block cross-tenant FK references.
- **RLS enabled on 88/88 tables; 0 tables without policies** (verified by SQL).
- `api_*` RPCs (`api_post_sale`, `api_receive_purchase`, `api_post_purchase_return`, `api_complete_stock_transfer`, `api_post_sale_return_financial`, …) are **SECURITY INVOKER** with pinned `search_path` → they inherit RLS. Correct.
- `adjust_stock()` is SECURITY DEFINER with pinned search_path.
- Migration history is rich and Z-Syst-specific (financial ledger foundation, idempotency keys, atomic transfers, integrity checks, RLS cleanup) — latest `20260926153850 validate_existing_integrity_checks`.

**Domain coverage (88 tables):** sales/POS (`sales`, `sale_items`, `sale_details`, `sale_returns`, `cash_registers`, `cash_register_transactions`, `coupons`, `loyalty_*`) · purchasing (`purchases`, `purchase_items`, `purchase_orders`, `goods_received_notes`, `grn_items`, `supplier_invoices`, `supplier_payments`) · inventory (`stocks`, `warehouse_stocks`, `warehouse_stock_batches`, `batch_lots`, `stock_movements`, `stock_transfers`, `stock_takes`, `stock_audits`, `fefo_*`) · pharma-specific (`batch_lots`, `controlled_substances_log`, `recall_events`, `traceability_logs`, `drug_interactions`, `prescriptions`, `prescription_refills`, `insurance_*`, `doctor_attention_*`) · finance (`financial_transactions`, `financial_audit_logs`, `expenses`, `incomes`, `payment_schedules`, `aging_reports`, `budget_*`) · SaaS billing (`subscription_plans`, `subscriptions`, `subscription_invoices`, `subscription_payments`, `subscription_logs`, `usage_records`, `coupons`, `coupon_redemptions`).

**Auth (after this session):** `site_url = https://app.z-syst.com`, allow-list = `app.z-syst.com`, `*.z-syst.com`, `*.vercel.app`, `localhost:3000`; email+password only; email confirmation required (`mailer_autoconfirm = false`); no OAuth providers; anonymous sign-ins disabled; refresh-token rotation on.
**Not yet configurable through the connector (dashboard/SQL only):** `password_min_length` (still 6), leaked-password protection `hibp` (off), CAPTCHA (off).

**Storage:** 0 buckets — nothing stored, no upload path exists yet.
**Realtime:** 0 tables in the `supabase_realtime` publication — needs enabling if the POS wants live updates.
**Edge Functions:** `z-syst-api` (verify_jwt = true, v4) — source ships as compiled ESZIP, so the TypeScript must be edited/redeployed from the original repo.

---

## 4. Security findings

| # | Severity | Finding | Status |
|---|---|---|---|
| S1 | 🔴 High | `warehouse_stock_batches_tenant_policy` was `FOR ALL TO public` (includes `anon`) + anon held full table grants | ✅ **FIXED** — policy scoped to `authenticated`, anon grants revoked (migration `z_syst_restrict_anon_access_20261031`) |
| S2 | 🟠 Medium | `anon` held blanket grants (incl. `TRUNCATE`) on `financial_transactions` | ✅ **FIXED** in same migration — `anon_grants_left = 0` verified |
| S3 | 🟠 Medium | Auth `site_url` pointed at `http://localhost:3000` with empty redirect allow-list | ✅ **FIXED** |
| S4 | 🟡 Low | `password_min_length = 6`, HIBP off | RECOMMENDED (dashboard only) |
| S5 | 🟡 Low | No realtime tables published, no storage buckets | RECOMMENDED (feature gap, not a vuln) |
| S6 | 🟠 Medium | `jobscope-research` → `research-control` edge function has **`verify_jwt = false`** (publicly callable) | REQUIRES APPROVAL (different project; unknown contract) |
| S7 | 🔴 Critical | Laravel app: unauthenticated `GET /update` runs `migrate`/`seed` (`routes/web.php:53-68`) | OPEN |
| S8 | 🔴 Critical | `CheckToken` middleware can delete `vendor/laravel` | OPEN (patch ready) |
| S9 | 🟠 High | Production MySQL password in plaintext in `zs-repo/.env` | OPEN — rotate |
| S10 | 🟡 Medium | `z-syst-site` Vercel deployment behind SSO → XPay crawler sees login wall | OPEN — dashboard toggle |

---

## 5. Decision executed: Supabase is the database of record, MySQL retired (2026-10-31)

**Owner instruction:** «قم بالاعتماد بالكامل على Supabase وحذف MySQL» — executed.

- Recovery point: `zsyst-backup/mysql-final-2026-10-31.sql.gz`, sha256 `f4f9cda6a8360898c0103f3083f5b9992ae1aae96a1f8b31355f5088bdfef192`.
- Data-loss check **before** deletion: MySQL held **0 operational rows** (products/sales/purchases/stocks/parties/businesses all 0). Only seed rows. Nothing of business value was destroyed.
- Code: `config/database.php` default → `pgsql` (mysql block deleted) · `config/backup.php` → `pg_dump` · `config/queue.php` default → `pgsql` · `.env` / `.env.example` / `.env.production.example` → Supabase pooler, leaked password removed · MySQL dump + export scripts removed from repo (archived).
- Supabase `subscription_plans` seeded with the EGP model (6 rows, verified) — see `zsyst-fix/07-supabase-cutover.md`.

**Schema delta (70 MySQL vs 88 Supabase):** 35 tables common · 35 MySQL-only · 53 Supabase-only.
Critical gaps: Supabase has **no `users` table** (identity = `auth.users` → `public.app_users`) and **no Spatie `roles`/`permissions`** (RBAC = `app_users.role`). So the PHP auth stack cannot run on Supabase unchanged.

**Open decision (REQUIRES APPROVAL):**
- **Path A (recommended):** retire the PHP runtime, build Supabase-native (React/Flutter + `api_*` RPCs + RLS). Zero server cost.
- **Path B:** keep Laravel as a façade → rewrite 65 MySQL migrations for Postgres, custom user provider over `auth.users`/`app_users`, remap `plans`→`subscription_plans`, re-implement RBAC. Weeks of work, high regression risk.

**Still owner-only:** delete the physical MySQL DB in the InfinityFree panel; rotate that password; supply `SUPABASE_DB_PASSWORD` (connector returns secrets hashed only).

## 5b. Path B implemented — Laravel façade over Supabase (2026-10-31)

Owner chose **Path B** («B اولا»). Delivered in `zs-repo` (see `zsyst-fix/08-path-b-laravel-on-supabase.md`):

- **88 Eloquent models auto-generated** from the live Postgres schema (`tools/generate-models.py`, source `database/supabase_schema.txt`), 201 belongsTo relations, `php -l` clean on all.
- **Auth via GoTrue**: `app/Support/Supabase/SupabaseAuthClient.php` + `app/Auth/SupabaseUserProvider.php`; driver registered in `AuthServiceProvider`, selected in `config/auth.php`. No password hashes in Postgres.
- **RLS enforcement**: `app/Http/Middleware/SetSupabaseContext.php` wraps each web request in a transaction with `SET LOCAL ROLE authenticated|anon` + `request.jwt.claims`, because Laravel connects as `postgres` (BYPASSRLS).
- **RBAC shim**: `app/Models/Concerns/HasSupabaseRoles.php` maps `app_users.role` → `config/supabase.php` permissions; 87 Blade `@can(...)` call sites resolved via `Gate::before`.
- **Security**: `GET /update` route DELETED · `CheckToken` middleware + import removed · backup→`pg_dump` · queue default `pgsql`.

**Known gaps:** `app_users` has no email (email lives in `auth.users`); 10+ legacy `App\Models\User` references unresolved (original models absent from this copy); runtime connection untested — blocked on `SUPABASE_ANON_KEY` / `SUPABASE_DB_PASSWORD` (connector returns secrets hashed only).

## 5c. Tenant isolation: the audit that found a real hole (2026-10-31)

Full write-up: `zsyst-fix/09-rls-audit-qa.md` · runnable harness: `zs-repo/tools/rls-audit.sql`.

- Installed `zsyst_qa.rls_audit()` (SECURITY DEFINER, `search_path=public,pg_temp`, EXECUTE revoked from `public`/`anon`/`authenticated`, granted to `postgres` only). Migrations: `z_syst_rls_audit_suite_20261031`, `..._static_only_...`, `..._refine_write_scope_...`.
- **Static results:** 88 tables / 0 without RLS · 0 tables with RLS but no policy · 0 policies targeting `anon` · 0 tenant tables missing an `ALL`/`INSERT` guard · 84/84 tenant tables reference `business_id` in a policy.
- `businesses` and `subscription_plans` have no client `INSERT` policy — **intentional** (provisioning / read-only catalogue), reclassified as an informational check.
- **Dynamic results:** an `authenticated` identity absent from `app_users` sees 0 rows in `app_users`/`products`/`sales`/`businesses` and 6 in `subscription_plans` (public catalogue). A cross-tenant `INSERT` into `products` fails with `42501 new row violates row-level security policy` — the error *is* the pass condition.
- Harness detail: `SET ROLE` is illegal inside a SECURITY DEFINER function (`42501 cannot set parameter "role" within security-definer function`), so impersonation checks must run at top level inside `BEGIN … ROLLBACK`.

**⚠️ CORRECTION (same day):** the static audit above passed while a real privilege-escalation hole existed, because it asked "does this tenant table have a write policy?" instead of "does every INSERT/ALL policy scope `business_id`?". Using the live anon key + real GoTrue JWTs against PostgREST proved it — full write-up in `zsyst-fix/11-critical-app-users-escalation-fixed.md`:

- **Hole 1:** `app_users_insert_self` (WITH CHECK `id = auth.uid()`, no `business_id`) let any authenticated user insert their own row into *any* tenant with role `owner`, then read that tenant's data. **Proven: HTTP 201.**
- **Hole 2:** `app_users_update_self` let a `staff` user set `role='owner'` and `business_id=<other tenant>`. **Proven: HTTP 200 twice.**
- **Root cause:** `private.current_business_id()` reads `business_id` from the caller's own `app_users` row, so whoever writes that column writes the result of every isolation policy in the system.
- **Fix (`z_syst_fix_app_users_privilege_escalation_20261031`):** dropped `app_users_insert_self`; added `private.guard_app_user_self_edit()` BEFORE UPDATE trigger rejecting client-side changes to `business_id`/`role`/`status`/`branch_id` (no-ops when `auth.uid()` is null, so provisioning and migrations still work).
- **After fix:** all three exploits return `403 42501`; a user editing their own display name still gets `200`; A sees only its own products/businesses, B likewise; `anon` gets `401` on every table.
- Audit function now at 7 checks (all ✅), incl. `4_every_insert_policy_scopes_business_id` and `6_app_users_self_edit_is_guarded` (`z_syst_rls_audit_per_policy_scope_20261031`, `..._final_shape_...`).
- **Test data fully removed** (0 app_users / 0 businesses / 0 products / 0 auth.users, 6 plans); `mailer_autoconfirm` temporarily `true` for the test, then restored to `false`.
- **Hardening applied:** `disable_signup=true` (verified: signup -> `422 signup_disabled`; admin/dashboard user creation still works). See `zsyst-fix/12-security-decisions-applied.md`.
- **CAPTCHA client side ready:** `SupabaseAuthClient::signInWithPassword()` accepts `captcha_token`; `LoginRequest` requires and forwards it only when `SUPABASE_CAPTCHA_ENABLED=true`; login view renders the hCaptcha widget with single-use-token reset. Secret verified via `api.hcaptcha.com/siteverify` (`invalid-input-response`, not `invalid-input-secret`). **Blocked on:** public hCaptcha site key + enabling it in the dashboard (see `zsyst-fix/13-hcaptcha-wiring.md` for the mandatory rollout order - client first, dashboard second, or every login breaks).
- **Still owner-side:** `password_min_length` (currently 6) and CAPTCHA are **not exposed by the connector** - allowed keys are only `disable_signup`, `external_email_enabled`, `external_phone_enabled`, `jwt_exp`, `mailer_autoconfirm`, `security_refresh_token_reuse_interval`, `security_refresh_token_rotation_enabled`, `site_url`, `sms_autoconfirm`, `uri_allow_list`. Both need the dashboard or an `sbp_` personal access token. `research-control`/`jobscope-probe` **do not exist** (404); the only deployed function is `z-syst-api` with `verify_jwt=true`.
- **Warning:** `update_auth_config` resets unspecified Auth fields to defaults (it nulled `security_captcha_provider`, `sms_provider`, `rate_limit_email_sent`). Re-read the full config after every call.

## 6. Roadmap

**P0 — Security**
1. (Supabase) ✅ anon access removed · ✅ auth redirects fixed.
2. (Laravel) remove `GET /update` + `CheckToken`; rotate the exposed MySQL password.

**P1 — Make Supabase the single source of truth**
3. Seed `subscription_plans` with the agreed EGP tiers: Standard 499 / Plus 899 / Max 1,499 monthly; yearly 4,990 / 8,990 / 14,990 — ✅ done (`z_syst_plan_prices_499_899_1499_20261031`), site redeployed.
4. Publish the POS-critical tables to `supabase_realtime`.
5. Store product images / pharmacy logos in Storage buckets with per-tenant policies.
6. Re-deploy `z-syst-api` from real TypeScript source (needs the source repo — GitHub blocked).

**P2 — Verification**
7. ✅ Tenant isolation proven at runtime 2026-10-31 (`zsyst_qa.rls_audit()` + impersonation tests — see §5c). pgTAP itself still unused; the SQL harness in `tools/rls-audit.sql` is the checking mechanism until pgTAP tests exist.
8. Enable password hardening + CAPTCHA in the dashboard.

**P3 — Product**
9. XPay checkout → `subscription_payments` → webhook → activate `subscriptions`.
10. Decide fate of the Laravel app (see §5).

---

## 7. Change log

| Date | Operation | Result |
|---|---|---|
| 2026-10-31 | hCaptcha secret verified with hcaptcha siteverify; full client-side CAPTCHA wiring added to Laravel (config, auth client, user provider, LoginRequest, login view, i18n, env examples) - ships disabled | COMPLETED (pending site key) |
| 2026-10-31 | Security decisions: `disable_signup=true` (verified 422) · password policy + CAPTCHA blocked by connector schema · `research-control` confirmed absent, `z-syst-api` already `verify_jwt=true` | 2 DONE / 2 BLOCKED |
| 2026-10-31 | **CRITICAL** privilege escalation via `app_users` found with real JWTs, fixed (dropped self-INSERT policy + guard trigger), re-tested | COMPLETED |
| 2026-10-31 | RLS audit harness + impersonation proof (88/88 protected, cross-tenant insert rejected) | COMPLETED |
| 2026-10-31 | Plan prices raised to 499/899/1,499 in DB and on site; redeployed `dpl_Ck4ReeuNam5CYmcG9VwzMG5XFAEV` (production alias live, HTTP 200) | COMPLETED |
| 2026-10-31 | Path B foundation: 88 models + GoTrue provider + RLS middleware + RBAC shim | COMPLETED (lint-verified) |
| 2026-10-31 | Removed `GET /update` and `CheckToken` from the Laravel app | COMPLETED |
| 2026-10-31 | MySQL retired; backup + repo cleanup + config switch to Supabase | COMPLETED |
| 2026-10-31 | `z_syst_seed_egp_plans_20261031` — 6 EGP plans seeded & verified | COMPLETED |
| 2026-10-31 | Connected Supabase connector | COMPLETED |
| 2026-10-31 | Discovered org + 2 projects, identified Z-Syst project | COMPLETED |
| 2026-10-31 | Restored paused project `atvttthrqsybjjwrbiyl` | COMPLETED (db ACTIVE_HEALTHY) |
| 2026-10-31 | Full schema/RLS/grants/auth audit via SQL | COMPLETED |
| 2026-10-31 | Migration `z_syst_restrict_anon_access_20261031` | COMPLETED (verified: anon grants 0) |
| 2026-10-31 | Auth `site_url` + redirect allow-list corrected | COMPLETED |
| 2026-10-31 | Vercel: `z-syst-site` deployed (6 Arabic pages) | COMPLETED (SSO-protected) |
| 2026-10-31 | Vercel: diagnosed `zyad` `NEXT_NO_VERSION` build error | COMPLETED (diagnosis; repo fix blocked) |
| 2026-10-31 | GitHub account access | **BLOCKED** — connector unsupported |
