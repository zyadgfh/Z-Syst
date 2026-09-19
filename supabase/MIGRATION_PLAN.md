# Supabase-first migration plan

## Target architecture

- Supabase PostgreSQL is the canonical application database.
- Supabase Auth is the canonical identity provider.
- Supabase Storage is the canonical object/file store.
- Supabase Realtime is used only for workflows that need live updates.
- Laravel remains the business/application layer during migration, using PostgreSQL through a server-side connection.
- Direct client access to exposed data is protected with PostgreSQL grants and RLS.
- Supabase service/secret keys are server-only.

## Migration order

1. Inventory Laravel migrations and classify tables as tenant-scoped, global, or system.
2. Create the PostgreSQL schema from the verified Laravel schema.
3. Introduce Auth user mapping between `auth.users` and application profiles.
4. Implement tenant RLS using the authenticated user's business membership.
5. Move files to Supabase Storage.
6. Migrate read/write services to PostgreSQL and remove MySQL assumptions.
7. Move realtime workflows to Supabase Realtime where beneficial.
8. Run compatibility tests and data reconciliation.
9. Remove legacy MySQL/Firebase dependencies only after production parity is verified.

## Non-negotiable security rules

- Every exposed tenant table must have RLS.
- Tenant isolation must be enforced in the database, not only Laravel middleware.
- Cross-tenant foreign-key relationships must be validated.
- No Supabase secret/service key may reach browser/mobile clients.
- Database changes must be committed as reproducible migrations.
- Do not modify or delete production data as part of schema migration without an explicit data-migration step.

## Current state

The connected Supabase project has PostgreSQL available but no application tables yet. Therefore this repository is intentionally in the schema/bootstrap stage; application tables must be derived from the existing Laravel migrations rather than invented independently.
