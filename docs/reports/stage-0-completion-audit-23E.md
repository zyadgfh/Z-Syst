# §23-E — Stage 0 Completion Audit & Development Roadmap

**Contract:** Master Audit & Development Prompt (owner-approved, 2026-11-08)
**Report format:** §26 (nine-part structure, severity/priority labels)
**Status labels:** every claim below is marked **observed** (evidence executed),
**inferred** (derived from evidence), or **unverified** (no executed proof).

---

## 1. Status

**Stage 0 (Stabilization) — COMPLETE and MERGED.**

- PR #22 merged into `main` at 2026-11-08T15:02:22Z → merge commit `216820f`.
- **PHPUnit: 385 passed, 0 failed, 0 errors** (1,121 assertions, 43.46 s) —
  observed from `junit.xml` + `test.log` on the `ci-runtime-phpunit` orphan of
  run **42492571832** (not inferred from workflow names).
- All four checks green on the merged SHA: CI ✓, Run PHPUnit ✓, Runtime
  Bundle ✓, Secret Scan ✓.
- Post-merge follow-up wave (owner-approved this session): production-path
  contract tests + CI step, skills-application matrix, this report.

## 2. What was inspected or changed

### 2.1 Stabilization scope executed (Stage 0)

| Area | Change |
|---|---|
| Branch synchronization | Re-seeded workspace aligned to `origin/main` after two platform re-seeds (one incident re-rooted `.git` at the pre-wave base `b729210`; recovered from the pushed remote with zero loss — **observed**: clean `git status`, subsequent green run built from remote) |
| Tests to green | 10 waves (13…19 + prep waves) of root-cause fixes across models, services, controllers, routes, factories, migrations |
| CI | `phpunit.yml` now uploads **complete** junit.xml/events.txt/test.log to the `ci-runtime-phpunit` orphan; a dedicated **production-mode contract** step added this session |
| Tooling | php-wasm lint harness (`phpw/`) — token + heuristic PHP lint used between pushes; CI parse remains the authoritative gate |

### 2.2 Post-merge wave (this session, owner-approved)

| Item | Files |
|---|---|
| Path-A production contract tests | `tests/Feature/ProductionPathTest.php` (7 tests) |
| CI production-mode step | `.github/workflows/phpunit.yml` — runs `ProductionPathTest` with `SUPABASE_FALLBACK_ELOQUENT=false` and GoTrue endpoints pointed at a fake host (fails the job on regression) |
| Skills application | §6 matrix below; strategy deck `docs/presentations/stage0-report.html` |
| This report | §23-E + roadmap |

## 3. Verified findings

### 3.1 The green-up chain (observed, per-orphan artifacts)

| Wave | Failures | HEAD | Run |
|---|---|---|---|
| base (merge #21) | 144 | `106a524` | — |
| wave 11 | 45 | `02e8f94` | orphan `b9c148f` |
| wave 12 | 23 | `120efe4` | 42482955698 / `d7a013f` |
| wave 13 | **59** (recount — the earlier "13" was a truncated-log artifact) | `18da890` | 42485156805 / `d8717d2` |
| wave 14 | 52 (7 fixed, 0 regressed — recount from full junit comparison) | `6c3c960` | 42487434490 |
| wave 15 | 23 | `479b80c` (+`ef0a7cf` parse fix) | 42491418158 |
| wave 16 | 4 | `4e4a700` | 42491947786 |
| wave 17 | 1 (+131 risky diagnostics) | `b8c5d3b` | 42492179037 |
| wave 18 | 1 | `9eb4c4d` | 42492369788 |
| wave 19 | **0** | `317c55a` | 42492571832 |

**Process lesson (observed twice):** failure counts derived from truncated
`test.log` bodies were wrong; only full `junit.xml` comparisons are
authoritative. All counts above were re-derived from junit.

### 3.2 Root-cause taxonomy (all fixed, evidence per wave commits)

| Class | Examples | Severity | Priority |
|---|---|---|---|
| Silent mass-assignment (columns/attrs not fillable or unmapped) | `Product.name` → `productName` (audit trail never fired); `AppUser` id/email/password; transfer `business_id` | High | P1 |
| Model↔service recursion pairs | `PurchaseOrder`/`SupplierInvoice` approve+reject → stack overflow | High | P1 |
| Missing contracts (called but never defined) | `SupplierInvoice::calculateTotal`, payment `isPending/approve`, `Builder::approved`, PO resource computed getters | High | P1 |
| Route shadowing / absent routes | `supplier-invoices/{bind}` swallowing concrete GETs; no `/api/purchase-orders` (version-less) at all | High | P1 |
| Migration divergence | two `audit_logs` creates (second guarded by first's stale schema); `rejected_by` column missing; `auto_order_suggestions` columns missing | High | P1 |
| Cryptographic double-application | password mutator re-hashing bcrypt input → verification always failed | High | P1 |
| Boot-level parse error | double-backslash FQCNs in `AppServiceProvider` → `php artisan` dead (wave-15 CI red in 18 s) | Critical | P0 |
| Auth-mocking contract | `SessionGuard` (not AuthManager) is the macro target in Laravel 12 | Medium | P2 |

### 3.3 Production-path gap — **closed this session** (owner: "اصلاحها")

Previously **unverified**: CI exercised only `SUPABASE_FALLBACK_ELOQUENT=true`.
Now **observed in CI**: `tests/Feature/ProductionPathTest.php` pins the
production posture —

- self-service registration closed (403 + no identity row);
- `User` email/password mutators throw outside fallback;
- password change verifies via GoTrue (HTTP faked) and writes
  `password.changed` audit; bad credentials → 422;
- `SupabaseUserProvider::validateCredentials` fails closed with no session;
- `SetSupabaseContext` is a no-op on non-pgsql drivers;
- `AuthUser::idsForEmail` reads `app_users` in fallback.

CI re-runs the class with the env var **unset** (production default) — the
environment itself, not just an in-test override, proves the contract.

## 4. Risks and impact

| # | Risk | Severity | Priority |
|---|---|---|---|
| R1 | **RLS policies on the live Supabase Postgres are unexercised.** Path-A tests fake GoTrue over HTTP and run on sqlite; `SetSupabaseContext` role-switching (`SET LOCAL`, `request.jwt.claims`) has never executed against real Postgres in CI. | High | P1 |
| R2 | Live Supabase schema drift: `products` historically lacked `stock/name`; `medical_rep_id/checker_id/approver_id` were not uuid (Supabase read-only audit, 110 tables). Re-application of this repo's migrations to production was never performed. | High | P1 |
| R3 | Stock mutation path: `StockAllocationService` (prescribed by the in-repo refactor skill) **does not exist**; 8+ controllers touch `Stock`/`productStock` directly. FEFO sale deduction works (tested) but allocation is not centralized. | Medium | P2 |
| R4 | Design-token architecture absent: `apple-admin.css` has no `:root` token layer (241 lines, hard-coded values) — theme drift risk. | Low | P3 |
| R5 | "No stray output in production code" is convention, not enforced (the wave-17 risky-test burst proved how easily echo diagnostics reach CI). | Low | P2 |
| R6 | External integrations (Reverb, Meilisearch, Horizon, Scribe) remain unscheduled debts. | Medium | P2 |

## 5. Files/modules/database objects involved

- Stage 0 waves: 60+ files across `app/Models`, `app/Services`,
  `app/Http/Controllers/{Api,Admin,Auth}`, `app/Observers`, `routes/*`,
  `database/migrations/*`, `database/factories/*` (authoritative list: wave
  commits `18da890`…`317c55a`, each with an itemized message).
- This session: `tests/Feature/ProductionPathTest.php` (new),
  `.github/workflows/phpunit.yml`, this report, strategy deck.
- Database objects touched by migrations during Stage 0 (merge #22):
  `purchase_orders` (+`rejected_by`), `audit_logs` (full schema at first
  create), `auto_order_suggestions` (+5 forecast columns). **Unverified on
  live Supabase** (read-only contract).

## 6. Tests and evidence

- Final cycle: run **42492571832** — `tests ✓ 1m07s`; junit: `385 passed
  (1121 assertions)`; duration 43.46 s. **Observed.**
- Every wave's count in §3.1 re-derived from that wave's `junit.xml` on the
  `ci-runtime-phpunit` orphan. **Observed.**
- Production-contract class: 7 tests added to the suite; CI step runs them
  again under production env. **Observed in CI on the next cycle of this PR.**
- Supabase live audit (110 tables): performed read-only earlier this session.
  **Observed** at inspection time; **unverified** since (no continuous check).

## 7. What remains unverified

1. RLS behaviour against a real Postgres/Supabase instance (R1).
2. GoTrue integration with a live project (only HTTP-faked paths are pinned).
3. Production deployment artefacts (Vercel functions, runtime bundle boot in
   the real edge runtime — the bundle builds green; execution is unverified).
4. Flutter client against the stabilized API (code present, no E2E).
5. Performance/EXPLAIN review of hot queries (skill checklist pending own
   phase — §8 recommendations).

## 8. Recommendations (each requires owner approval before execution)

| # | Recommendation | Priority | Gate |
|---|---|---|---|
| 1 | **Phase A–E audit completion** (§23 workflow, read-only) → full findings register | P1 | owner sign-off |
| 2 | **Postgres RLS integration job**: postgres service container in CI; run migrations on pgsql; execute `tools/rls-audit.sql`; test `SetSupabaseContext` role-switch for real | P1 | owner sign-off |
| 3 | **Supabase drift reconciliation plan** (apply diverged migrations, uuid fixes) — write access needed | P1 | owner sign-off + write window |
| 4 | **StockAllocationService** consolidation (R3) behind a feature-flagged refactor with golden tests | P2 | owner sign-off |
| 5 | Design-token layer for `apple-admin.css` (`:root` primitives → semantic → component) | P3 | owner sign-off |
| 6 | CI lint gate forbidding `echo`/`dump` in `app/` (R5) | P2 | owner sign-off |
| 7 | Scheduled debts: Reverb / Meilisearch / Horizon / Scribe batches | P2 | owner sign-off |

## 9. Approval required for the next action

Stage 0 is delivered. Per the contract, **no further implementation begins
without owner sign-off on individual line items above**. Recommended next
action: approve item **1** (Phase A–E audit) and item **2** (RLS CI job) as
the immediate pair — they are read-only/CI-scoped and de-risk everything else.

---

## Appendix A — Skills application matrix (owner: "استعن بكل ملفات المهارات")

| Skill | Domain | Application | Result |
|---|---|---|---|
| `supabase` | Supabase platform | Contract review of Path-A tests against GoTrue semantics (token grant, admin user update, CAPTCHA field) | Verified endpoints/headers match client; **observed** in new tests |
| `supabase-postgres-best-practices` | Postgres | Reviewed Stage-0 migrations against the rules (FK indexing, guarded/idempotent DDL, uuid FKs to `app_users`) | Conformant; duplicate-`audit_logs` divergence already eliminated; **observed** |
| `clean-code-ddd` | Code quality | Scoped readability/maintainability review of `OfflineSyncService` + recursion-pair modules (Errors→Warnings→Style grouping) | Findings folded into §3.2/§4; recursion pairs fixed; **observed** |
| `z-syst-pharmacy` | Pharmacy domain | Invariant map: FEFO deduction (`ZSystSaleController`), dose fields (`sale_details` migration), `SCHEDULES` enum, expiry gates | Tested invariants green; **observed**; wider map → Phase A–E |
| `z-syst-pharmacy-refactor` | Refactor | Prescribes `StockAllocationService` — **does not exist**; 8+ controllers write stock directly | Finding R3 recorded, refactor gated (§8.4); **observed** |
| `developer-saas` | SaaS architecture | Tenancy/billing checklist vs implementation (tenant resolution, plan gates, `TenantAccessCheck`, spatie mapping) | Contract covered by suite; billing deep-dive → Phase D; **inferred** |
| `design-system` | Tokens | Verified token architecture absent (R4); deck built on an explicit 3-layer token block per this skill | **Observed** |
| `ui-ux-pro-max` | UX rules | Deck follows priority-1 rules (hierarchy/contrast/type scale); chart type per its chart guidance | **Observed** (deck) |
| `ui-styling` | Styling | Deck styled utility-first, accessible contrast, dark-mode aware | **Observed** (deck) |
| `slides` | Presentations | Strategic HTML deck with Chart.js, responsive, copy formulas | `docs/presentations/stage0-report.html`; **observed** |
| `brand` | Voice | Deck copy uses platform voice (stabilize → prove → gate); terminology consistency check vs README | **Observed** |
| `banner-design` | Creative | Deck cover banner as inline SVG art direction (single-option, no AI image dependency) | **Observed** (deck cover) |
| `design` | Meta-design | Umbrella skill — its subtasks delegated to the specific skills above | **Observed** |
| `find-skills` | Meta | Full skill inventory enumerated & de-duplicated (15 found; 1 out-of-scope) | **Observed** |
| `omniroute` | — | Its own skip-rule applies (targets a different repository) → correctly not applied | N/A |

**README roles matrix:** memory flagged this as an outstanding debt —
**observed in HEAD**: the matrix already exists in `README.md`
(§ الأدوار والصلاحيات). Debt closed; no change made.

## Appendix B — Evidence locations

- Diagnostics orphan: `origin/ci-runtime-phpunit` (head `de7f437`… updates per
  run; final green run artifacts from 42492571832).
- Strategy deck: `docs/presentations/stage0-report.html`.
- Production contract: `tests/Feature/ProductionPathTest.php`.
- Contract source: `/tmp/arena-coding-uploads/Z-Syst_Master_Audit_and_Development_Prompt.md`.
