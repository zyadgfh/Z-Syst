# Security and Production Audit — 2026-09-22

## Immediate security action

A real-looking Gmail SMTP app password was found in the tracked `.env.example` file. The value has been removed from the current branch and replaced with an empty placeholder.

**Required outside GitHub:** revoke/rotate the exposed Gmail App Password immediately. Removing it from the latest file does not invalidate a credential that was already exposed.

## Repository security controls

- `.env` is ignored by Git.
- Secret scanning is configured through GitHub Actions/Gitleaks.
- The tracked environment example must contain placeholders only.
- Production secrets must be injected through the deployment environment, never committed.

## Architecture alignment

The repository currently uses Laravel 10.50.2 / PHP 8.1+ according to `composer.json`. Documentation has been aligned with the actual dependency version and the Supabase/PostgreSQL backend.

## Supabase state

The connected Supabase project is active and healthy, with PostgreSQL 17.6. RLS is enabled across the reviewed public application tables.

The database is not empty: it contains the tenant, identity, inventory, purchasing, sales, returns, transfers, finance, subscriptions, audit, and traceability domains.

## Index findings

Supabase reports many unused indexes. These are not automatically defects. Index removal is deferred until representative production workload/query statistics are available; dropping indexes blindly could regress writes or rare critical queries.

## Analytics integration

The product analytics service now queries the current Supabase snake_case schema:

- `sales.sale_date`
- `sales.total_amount`
- `sale_details.quantities`
- `sale_details.price`
- `products.product_name`
- `products.product_code`
- `stock_movements.created_at`
- `stock_movements.warehouse_id`

Product-specific revenue is calculated from sale-detail quantity × unit price, while all-product revenue uses the sale header total.

## Verification status

The feature test suite was expanded, but a local PHPUnit run was not executed from this ChatGPT environment. GitHub Actions should be used as the authoritative build/test verification after the commits land.

## Remaining production tasks

1. Rotate the exposed SMTP credential.
2. Verify GitHub Actions after the latest commits.
3. Run end-to-end Laravel → Supabase tests with a non-production test tenant.
4. Audit tenant isolation under authenticated cross-tenant requests.
5. Reconcile remaining Laravel model/migration naming differences with the Supabase schema.
6. Review financial ledger and return/refund invariants with transaction-level tests.
7. Measure real query workload before pruning unused indexes.

## Live Supabase integrity spot-checks

The following read-only checks returned zero violations at audit time:

- Negative `financial_transactions.amount`: 0
- Negative `cash_register_transactions.amount`: 0
- Orphan `sale_returns.sale_id`: 0
- Orphan `purchase_returns.purchase_id`: 0
- Cross-tenant `sale_details` → `sales`: 0
- Cross-tenant `stock_movements` → `products`: 0

These are spot-checks, not a substitute for the full transaction and tenant-isolation test suite.

## Supabase advisors

- Security advisor: 0 lints.
- Performance advisor: 228 unused-index notices. These remain intentionally untouched until real workload data is available.
