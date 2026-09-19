# Z-Syst — Supabase Migration Status

## Current state

Z-Syst is in an active migration from the legacy Laravel/MySQL application architecture to a Supabase-native architecture.

### Supabase-native and deployed

- PostgreSQL schema and tenant model
- Row Level Security across public application tables
- Supabase Auth integration foundation via `app_users`
- Atomic sale posting + FEFO
- Atomic purchase receiving
- Atomic sale/purchase returns
- Atomic stock transfers
- Cash register and coupon transactional functions
- Warehouse/batch allocation ledger: `warehouse_stock_batches`
- Recall-aware sale allocation
- Audit and traceability logging
- Supabase Edge Function: `z-syst-api`
  - `post_sale`
  - `receive_purchase`
  - `complete_stock_transfer`
  - `post_purchase_return`

## Still being migrated

The legacy Laravel application remains in the repository and still contains:

- REST/API controllers
- Admin web routes/UI
- Laravel Sanctum authentication flows
- Eloquent models/services
- Laravel queues/scheduling
- Redis-backed cache/session/queue configuration
- Legacy MySQL development/runtime assumptions
- Existing Flutter client integration

These are not considered migrated merely because equivalent database functions exist.

## Migration rule

The Laravel layer must not be deleted until each domain has:

1. Supabase schema/RLS coverage.
2. Supabase Auth authorization.
3. Supabase RPC/Edge Function API coverage where transactional logic is required.
4. Frontend/client integration.
5. Automated tests for tenant isolation and transaction integrity.
6. Production deployment verification.

## Target architecture

Client applications -> Supabase Auth -> Supabase Database/RLS + RPC -> Supabase Edge Functions for server-side orchestration/integrations.

Redis/Laravel workers are retained only for workloads that genuinely require an external worker after the migration audit.

## Verification

Security Advisor is expected to remain clean after each migration batch. Legacy services are considered removable only after dependency searches show no active client/runtime references.
