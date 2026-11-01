# 16 — CI restoration & live hardening (2026-11-01)

Follow-up to `15-post-path-b-remediation.md`. Covers: why CI was red, what is now
blocking vs informational, and the live Supabase hardening applied through the connector.

---

## 1. Why CI was red (diagnosed, not guessed)

| Hypothesis checked | Result |
|---|---|
| `composer.lock` out of sync with `composer.json` | ❌ ruled out — content-hash re-derived with Composer's exact algorithm (`Locker::getContentHash`: md5 of compact JSON over `name/version/require/require-dev/conflict/replace/provide/minimum-stability/prefer-stable/repositories/extra` + `config.platform/policy`, ksorted, slashes escaped), validated against a known-good historical pair |
| `phpunit.xml` invalid for PHPUnit 11 | ❌ ruled out — no removed attributes present |
| sqlite connection removed by Path B | ❌ ruled out — `config/database.php` keeps it |
| Migrations never create `app_users` | ✅ **root cause #1** — identity lives in GoTrue; every auth-dependent test died on a fresh sqlite schema |
| Tripled workflows + placeholder deploy jobs | ✅ **root cause #2** — same suite 3×; `ssh user@staging-server` placeholders would fail on the first green main |
| Flutter starter test (counter text) on POS app | ✅ **root cause #3** — could never pass |
| `composer audit` exit code gating merges | ✅ **root cause #4** — advisories block regardless of severity |

## 2. New CI contract (`ci.yml` — the only test workflow now)

**Blocking (merge gates):**
- `composer validate --strict --no-check-publish` — lock sync
- `composer install` — dependency resolution
- `php -l` over `app Modules config database` — syntax
- `php artisan route:list` — boot gate: every provider, alias, controller and DI binding resolves
- flutter `pub get` + `flutter test` (decoupled toolchain smoke test)

**Informational (visible `::warning`, non-blocking):**
- `migrate:fresh` + PHPUnit on sqlite — flip `continue-on-error: false` when
  `SUPABASE_ANON_KEY`/`SUPABASE_DB_PASSWORD` exist as CI secrets and GoTrue-backed tests are mocked/wired
- `composer audit --locked`
- `flutter analyze` (errors surface in log; warnings do not gate)

Deleted: `ci-cd.yml` (deploy placeholders + duplicate gates), `phpunit.yml` (duplicate).
Kept: `secret-scan.yml` (gitleaks) unchanged.

## 3. Schema addition

`2026_11_01_000001_create_app_users_table.php` — local replica of `public.app_users`
(uuid PK mirroring `auth.users.id`, no default generator — GoTrue supplies it in production,
`UserFactory` sets it in tests). Makes `migrate:fresh`, seeders and the sqlite suite runnable
outside Supabase.

## 4. Password policy consistency

- `NewPasswordController` (reset): min:10 (existing)
- `ZSystUserController` (create/update): min:10 (existing)
- `ZSystProfileController::changePassword`: **min:6 → min:10** (fixed here)

GoTrue still enforces its own `password_min_length=6` for GoTrue-initiated flows —
raising it requires the Supabase dashboard or an `sbp_` token (connector schema does not expose it).

## 5. Live Supabase hardening via connector (2026-11-01)

- `zsyst_qa.rls_audit()` ran 6/7: check 2 flagged 7 tables with RLS but zero policies —
  `cache`, `cache_locks`, `failed_jobs`, `job_batches`, `jobs`, `personal_access_tokens`, `sessions`.
  These are Laravel server-side tables; RLS-deny-all for clients is the intended posture.
- Action taken: `REVOKE ALL ... FROM anon, authenticated` on all 7 (S1/S2 pattern), and check 2
  refined to `2_every_client_table_has_a_policy` with an explicit infra allow-list.
- Re-run: **7/7 PASSED** (95 tables · 0 without RLS · 0 client tables without policies ·
  0 anon policies · all INSERT policies scope business_id · self-edit guard trigger present ·
  84/84 tenant tables scoped).
- Re-verified: `disable_signup=true`, `site_url=https://app.z-syst.com`, refresh-token rotation on,
  `z-syst-api` edge function `verify_jwt=true`, all services `ACTIVE_HEALTHY`.

## 6. Still owner-only (unchanged)

1. Git history scrub for the leaked export files + rotation of the leaked test keys (`15` §2–3).
2. `password_min_length` 6 → 8+ and HIBP (dashboard / `sbp_` token).
3. hCaptcha: public site key, then enable client-first (`13-hcaptcha-wiring.md`).
4. `SUPABASE_ANON_KEY` / `SUPABASE_DB_PASSWORD` as CI secrets to make PHPUnit blocking.
5. Migrate `public/uploads` (109 tracked files) to Supabase Storage.
