# 15 — Post-Path-B Remediation (2026-10-31)

Follow-up to `08-path-b-laravel-on-supabase.md`, `11-critical-app-users-escalation-fixed.md`
and the security review of branch `arena/be673a0b-z-syst`. This document records what the
remediation PR changed on top of `4172663e`, and — critically — what **cannot** be fixed by a
commit: secrets already living in git history.

---

## 1. What this PR changed

| Commit | Change |
|---|---|
| `security: remove leaked MySQL export` | Deleted `database/database_export.sql` (Stripe / Paystack / Tap `sk_test_*` secret keys, bcrypt hashes + `remember_token` for superadmin/admin/manager, live InfinityFree DB name) and the two `export_*.php` generators. |
| `security: apply Supabase RLS context to API requests` | `SetSupabaseContext` was only in the `web` middleware group — every `/api/v1` request (the whole Flutter POS traffic) ran as `postgres` (BYPASSRLS) and never touched the 88 tenant RLS policies. It now runs after `auth:sanctum` + `tenant.check` on both API route groups via the new `supabase.context` alias. Unauthenticated auth routes intentionally stay outside (identity must resolve pre-auth). |
| `security: lock down CORS` | `allowed_origins: ['*']` → `CORS_ALLOWED_ORIGINS` env (default `https://app.z-syst.com` + localhost dev), Vercel preview pattern, credentials enabled, preflight cached. |
| `fix: stop calling env() at request time` | 11 runtime `env()` call sites → `config()` (mail envelopes, demo-mode, cache TTL, DB label in backups, message gating). Under `config:cache` these silently returned null. |
| `chore: repo hygiene` | Removed the byte-duplicate inner `z-syst/` (kept newer `functions/`), one-off branding/refactor scripts, `routes.txt` dump, `temp_plans.html` (leaks local paths), orphan 1.5 MB `logo.png`, `storage/installed`, stale `workspace/*_findings.json`; `TODO.md` now points at the KB roadmap. |
| `chore: harden .gitignore` | `.env.*` blocked except committed templates; dumps/caches/installer marker ignored; `/public/uploads` ignored for *new* files. |
| `docs: align README + LICENSE` | README says Laravel 12 / PHP 8.2 / Supabase now; mobile app marked shipped; LICENSE (MIT) added. |

---

## 2. ⛔ Git history still contains the leak — owner runbook

Deleting files in a commit does **not** remove them from history. Anyone who clones the repo
today can still run `git show <old-sha>:database/database_export.sql` and read the payment
keys and user hashes. Required (destructive, coordinates with all clones):

```bash
# 1. Mirror-clone fresh
git clone --mirror git@github.com:zyadgfh/Z-Syst.git zs-scrub && cd zs-scrub

# 2. Purge leaked paths from ALL history
pip install git-filter-repo
git filter-repo --invert-paths \
  --path database/database_export.sql \
  --path database/export_simple.php \
  --path database/export_to_mysql.php \
  --path .env.production \
  --path zs-repo/.env

# 3. Force-push rewritten history
git push --force --all && git push --force --tags
```

Then: every collaborator re-clones (old clones will re-push the leak), and GitHub Support is
asked to garbage-collect orphaned objects / cached views of the deleted files.

## 3. 🔑 Credentials to rotate (cannot be done from a commit)

| Secret | Where it leaked | Action |
|---|---|---|
| Stripe `sk_test_YKy…`, Paystack `sk_test_cf3…`, Tap `sk_test_Kbf…` | `database_export.sql` (lines 291–302) | Rotate in each dashboard even though they are test keys |
| superadmin/admin/manager bcrypt hashes + `remember_token` | same file | Passwords were migrated to GoTrue — confirm no legacy hash reuse; tokens died with MySQL |
| InfinityFree MySQL password (KB §4 S9) | `zs-repo/.env` (local copy) | Rotate in panel, then delete the physical DB (KB §5 owner-only list) |
| `SUPABASE_DB_PASSWORD` / service keys | never committed — hashed-only via connector | Keep out of the repo; store in Vercel/Supabase secrets only |

## 4. Owner-side dashboard items (unchanged from KB)

1. `password_min_length` 6 → 8+ (connector cannot set it; needs dashboard or `sbp_` token).
2. hCaptcha: enable **client first, dashboard second** (`13-hcaptcha-wiring.md`) before launch.
3. After enabling `SUPABASE_ENFORCE_RLS=true` in the runtime env, run `tools/rls-audit.sql` (7 checks).
4. Migrate `public/uploads` (109 tracked files) to Supabase Storage, then `git rm -r --cached public/uploads`.

## 5. Known runtime gaps carried from Path B (not addressed here — needs a booted environment)

- Runtime DB/Auth connection still unverified (blocked on `SUPABASE_ANON_KEY` / `SUPABASE_DB_PASSWORD`).
- 10+ legacy `App\Models\User` references flagged in KB §5b.
- PHP-level `TenantAccessCheck` remains as defense-in-depth; RLS is now the primary boundary for API traffic.

## 6. Verification checklist for this PR

- [ ] `php artisan route:list` shows `supabase.context` on `/api/v1` protected groups
- [ ] CORS preflight from a foreign origin returns 403-style denial
- [ ] `grep -rn "env(" app/` → only `SystemSettingController` remains
- [ ] Fresh clone: no `database/database_export.sql`, no `z-syst/`, no junk files
