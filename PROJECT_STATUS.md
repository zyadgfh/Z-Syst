# Z-Syst Project Status

Last reviewed: 2026-09-26

## Current architecture

- Laravel 10.50.2 / PHP 8.1+
- Sanctum bearer authentication for API routes
- Multi-tenant application using `business_id`
- Tenant resolution through `TenantResolver`
- Tenant context middleware and explicit tenant access middleware
- PostgreSQL/Supabase is the current production database direction
- Redis is used for selected analytics/cache workloads
- Flutter client is included in the repository

## Security hardening completed on this branch

- Unauthenticated requests can no longer select a tenant through `business_id`.
- Regular users are bound to their authenticated `business_id`.
- Superadmins can explicitly select a tenant when required.
- Authenticated API routes now pass through `tenant.check`.
- Route-bound Eloquent models with a `business_id` are checked against the authenticated tenant.
- `User::business_id` is no longer mass assignable.
- Regression tests cover tenant selection, cross-tenant route-model access, same-tenant access, superadmin access, and user mass-assignment protection.
- CI now validates Laravel route bindings, dependency compatibility, code style, dependency audit, Laravel tests, Flutter analysis, and Flutter tests.

## Functional domains declared by the current API

The current API route map declares endpoints for:

- authentication and password recovery
- products, stock, purchases, sales and returns
- prescriptions and drug interactions
- FEFO and expiry alerts
- sales prediction and auto-order
- inventory turnover
- stock audit and financial audit
- insurance and claims
- warehouses
- traceability and recalls
- loyalty/CRM
- receipts
- supplier invoices, purchase orders and GRN
- subscriptions and reporting

A route declaration is not considered feature verification. The CI route-list gate and feature/E2E tests must confirm that every declared controller resolves and behaves correctly.

## Supabase verification — 2026-09-26

- Active project: Supabase PostgreSQL 17.6.
- 88 public tables were checked; all 88 have RLS enabled.
- Supabase security advisor currently reports 0 security lints.
- PostgreSQL integrity checks returned zero violations for negative stock, invalid transfers, invalid coupon values, invalid cash-register balances, invalid cash transactions, and invalid coupon redemptions.
- Previously `NOT VALID` integrity constraints were validated successfully in Supabase and the equivalent PostgreSQL-aware Laravel migration was added to the branch.
- The performance advisor reports 228 unused-index notices. These are informational and are intentionally not removed until production workload/query statistics justify each removal.

## Agent skill alignment

The implementation follows the repository's Z-Syst pharmacy skill, Clean Code/DDD rules, UI/UX Pro Max guidance where UI changes apply, and the project's security/vulnerability scanning workflow. Changes use minimum-footprint refactors, service-layer business logic, database-first verification, regression tests, and explicit release gates.

## Verification status — 2026-09-26

- Latest Secret Scan: the last completed run passed.
- The first consolidated CI run exposed environment/dependency mismatches before tests: PHP 8.2 was incompatible with locked `maennchen/zipstream-php 3.2.2`, Flutter 3.24.3 was below the locked SDK floor of 3.27.0, and `composer validate --strict` treated existing version-constraint warnings as errors.
- CI was corrected to PHP 8.3, Flutter 3.27.0, and non-strict Composer validation. The orphaned `.agents/skills/skills` gitlink was also removed from the hardening branch. A fresh CI cycle is now the verification gate.

## Remaining release gates

### P0 — must pass before production

1. Green Laravel CI run including route-list validation and full PHPUnit suite.
2. Green Flutter analysis/test run.
3. Tenant isolation tests across representative resources.
4. Authorization tests for every state-changing endpoint.
5. Financial transaction invariants for sales, payments, returns and refunds.
6. Inventory invariants for purchases, sales, returns, adjustments and transfers.
7. Production-like Laravel-to-Supabase E2E tests using a dedicated non-production tenant.
8. SMTP credential rotation/revocation if the previously exposed credential was ever active.
9. Backup restore test, not only backup creation.
10. Cross-tenant negative tests against authenticated requests.

### P1 — production hardening

- Audit all raw queries and report/analytics services for tenant predicates.
- Verify policies/permissions for admin and operational endpoints.
- Reconcile Laravel models/migrations with the actual Supabase schema.
- Add idempotency protection to payment/financial write operations where applicable.
- Add audit logging for security-sensitive administrative actions.
- Add monitoring/alerting for authentication failures and authorization denials.
- Benchmark database indexes before removing unused indexes.

### P2 — product completeness

- Verify the insurance lifecycle end-to-end.
- Verify multi-warehouse transfers and warehouse-level permissions.
- Verify batch traceability and recall workflows.
- Verify loyalty/CRM behavior.
- Verify receipt generation/download/printing with the mobile client.
- Complete mobile API E2E coverage.

## Important rule

Do not mark a feature as complete merely because its route exists. A feature is complete only when:

`route -> authentication -> authorization -> tenant isolation -> validation -> service logic -> database transaction -> response -> regression test`

all pass.
