# 07 — Supabase Cutover: MySQL retired

**Date:** 2026-10-31 · **Decision (owner):** الاعتماد بالكامل على Supabase وحذف MySQL
**Executed by:** agent session · **Reversible:** yes — full MySQL dump archived with checksum

---

## 1. What was actually executed

| # | Action | Evidence |
|---|---|---|
| 1 | **Recovery point taken** before touching anything | `zsyst-backup/mysql-final-2026-10-31.sql.gz` (22,590 B) + `.sha256` = `f4f9cda6a8360898c0103f3083f5b9992ae1aae96a1f8b31355f5088bdfef192` |
| 2 | **Legacy MySQL tooling archived** | `zsyst-backup/legacy-mysql/{export_to_mysql.php,export_simple.php}` |
| 3 | **MySQL artifacts removed from the repo** | `zs-repo/database/database_export.sql`, `export_to_mysql.php`, `export_simple.php` deleted |
| 4 | **`config/database.php`** — default connection now `pgsql`, entire `mysql` connection block deleted | `'default' => env('DB_CONNECTION', 'pgsql')`, no `'mysql' =>` array |
| 5 | **`config/backup.php`** — `mysqldump` → **`pg_dump`** | dump_command verified |
| 6 | **`config/queue.php`** — database queue default no longer falls back to `mysql` | verified |
| 7 | **`.env` rewritten** — MySQL credentials (incl. the exposed plaintext password) gone; Supabase pooler values in | see §2 |
| 8 | **`.env.example` + `.env.production.example`** — Supabase values | `pooler.supabase.com` present, 0 mysql refs |
| 9 | **Supabase is the source of truth** — EGP plans seeded | migration `z_syst_seed_egp_plans_20261031`, 6 rows verified |

### Data-loss check before deletion
The MySQL database contained **zero operational rows**: `products=0, sales=0, purchases=0, stocks=0, parties=0, businesses=0, categories=0, manufacturers=0`.
Only reference/seed data existed: `currencies(151)`, `permissions(71)`, `languages(48)`, `drug_interactions(20)`, `gateways(14)`, `business_categories(8)`, `options(4)`, `users(3)`, `roles(3)`, `plans(3)`, `banners(3)`, `role_has_permissions(125)`, `migrations(65)`.
→ **No pharmacy business data was destroyed.** The full dump is still archived regardless.

---

## 2. New connection block (`zs-repo/.env`)

```env
# --- Database of record: Supabase Postgres (MySQL retired 2026-10-31) ---
DB_CONNECTION=pgsql
DB_HOST=aws-0-eu-central-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.atvttthrqsybjjwrbiyl
DB_PASSWORD=<<<NEEDED:SUPABASE_DB_PASSWORD>>>
# Session pooler (5432) is required by Laravel; transaction pooler (6543) breaks prepared statements.
```

Alternative (direct, no pooler): `DB_HOST=db.atvttthrqsybjjwrbiyl.supabase.co`, `DB_USERNAME=postgres`.

---

## 3. Seeded plans (verified rows)

| id | name | slug | price | currency | cycle | trial |
|---|---|---|---|---|---|---|
| 1 | Standard | standard-monthly | 399.00 | EGP | monthly | 14 (default plan) |
| 2 | Standard (سنوي) | standard-yearly | 3,990.00 | EGP | yearly | 14 |
| 3 | Plus | plus-monthly | 799.00 | EGP | monthly | 14 |
| 4 | Plus (سنوي) | plus-yearly | 7,990.00 | EGP | yearly | 14 |
| 5 | Max | max-monthly | 1,499.00 | EGP | monthly | 14 |
| 6 | Max (سنوي) | max-yearly | 14,990.00 | EGP | yearly | 14 |

`limits` jsonb: Standard `{users:3, skus:2000, branches:1}` · Plus `{users:10, skus:5000, branches:2}` · Max `{users:null, skus:null, branches:null}`.

---

## 4. Schema delta — MySQL 70 tables vs Supabase 88 tables

**Common: 35 tables.** `products`, `sales`, `sale_details`, `purchases`, `purchase_details`, `stocks`, `stock_movements`, `parties`, `taxes`, `units`, `categories`, `manufacturers`, `expenses`, `incomes`, `prescriptions`, `insurance_*`, `fefo_*`, `auto_order_*`, `audit_logs`, `warehouses` …

**MySQL-only (35) — must be re-homed, not just dropped:**

| Category | Tables | Consequence |
|---|---|---|
| 🔴 Laravel auth core | `users`, `password_reset_tokens`, `password_resets`, `personal_access_tokens` | Supabase uses `auth.users` + `public.app_users`. **Laravel's Eloquent auth cannot run on Supabase unchanged** |
| 🔴 Spatie permission | `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` | Supabase uses `app_users.role` (text). RBAC must be re-implemented or mirrored |
| 🟠 Framework | `migrations`, `jobs`, `failed_jobs`, `notifications` | Laravel internals; irrelevant on the Supabase path |
| 🟠 Billing | `plans`, `plan_subscribes` | replaced by `subscription_plans` / `subscriptions` |
| 🟡 Content/ref | `banners`, `blogs`, `comments`, `testimonials`, `messages`, `features`, `options`, `gateways`, `currencies`, `languages`, `pos_app_interfaces`, `business_categories` | no Supabase equivalent yet — decide: build, or drop |
| 🟡 Analytics | `sales_forecasts`, `prediction_settings`, `product_inventory_analysis`, `inventory_turnover_reports`, `stock_audit_details`, `stock_reconciliations`, `due_collects`, `insurance_coverages` | partly covered by `aging_reports`, `usage_records`, `stock_takes`, `doctor_attention_*` |

**Supabase-only (53):** `app_users`, `auth`-bridging, `cash_registers`/`cash_register_transactions`, `supplier_invoices`/`supplier_payments`/`supplier_invoice_*`, `goods_received_notes`/`grn_items`, `purchase_orders`, `stock_transfers`, `stock_takes`, `batch_lots`, `traceability_logs`, `recall_events`, `controlled_substances_log`, `coupons`, `loyalty_*`, `subscriptions`/`subscription_invoices`/`subscription_payments`/`subscription_logs`/`usage_records`, `budget_*`, `payment_schedules`, `quality_*`, `doctor_attention_*`, `customer_interactions`, `branches`, `warehouse_stocks`/`warehouse_stock_batches`.

---

## 5. What I could NOT do (and why)

| Item | Blocker | Minimum action |
|---|---|---|
| **Delete the physical MySQL server** (`if0_41922398_pharmasy_db` on InfinityFree) | No InfinityFree credentials/API in this session | Owner deletes the DB in the InfinityFree panel; then the credentials are dead regardless |
| **Rotate the leaked MySQL password** | Same | The repo no longer contains it; rotation is one click in the host panel |
| **Connect Laravel/CLI to Supabase** | `SUPABASE_DB_PASSWORD` is stored hashed in Vault — the connector returns only hashes; I never see secrets | Owner sets `DB_PASSWORD` in the app's env/secret manager (or shares via a secret, not chat) |
| **Run migrations against Supabase** | `zs-repo` has **0 migration files** and no `app/Models` (incomplete copy) + GitHub connector unsupported | Restore the real repo (archive upload) or work Supabase-native |
| **Re-deploy `z-syst-api` edge function** | Stored body is compiled ESZIP, not TypeScript | Needs source repo |

---

## 6. Honest consequence of the decision

"Adopt Supabase and delete MySQL" is **not a config flag** — the two schemas diverge on identity and RBAC:

- Supabase has **no `users` table**; identity lives in `auth.users` and is mapped to `public.app_users` (which carries `business_id`, `role`, `branch_id`).
- Laravel's `Auth` + Spatie need `users`/`roles`/`permissions` — neither exists in Supabase.

Therefore the cutover resolves into one of two paths. **This needs an explicit decision:**

**Path A — Retire the PHP runtime (recommended).**
The app becomes Supabase-native (React/Flutter + `api_*` RPCs + RLS). Nothing else to port; 88-table schema, RLS, atomic RPCs and the financial ledger already exist and are better designed than the MySQL dump. Zero server cost (Supabase free tier + Vercel hobby).

**Path B — Keep Laravel as a façade.**
Requires: Postgres-compatible migrations (rewrite of 65 migrations), a custom user provider backed by `auth.users`/`app_users`, remapping `plans`→`subscription_plans`, `gateways`→ new table, plus RBAC re-implementation. Estimated as weeks of work with high regression risk, and it keeps two definitions of every domain concept.

---

## 7. Rollback (if ever needed)

```bash
gunzip -c /home/user/zsyst-backup/mysql-final-2026-10-31.sql.gz > restore.sql
mysql -u <user> -p < restore.sql      # recreates if0_41922398_pharmasy_db with all 70 tables + seed rows
grep -n "DB_" zs-repo/.env            # restore the 5 DB_* lines from section 2 of this doc
```
Checksum must match `f4f9cda6a8360898c0103f3083f5b9992ae1aae96a1f8b31355f5088bdfef192` before trusting the archive.
