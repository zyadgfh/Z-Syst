-- ============================================================================
-- Z-Syst · Supabase row-level-security audit
-- Project: atvttthrqsybjjwrbiyl
-- Installed by migrations:
--   z_syst_rls_audit_suite_20261031
--   z_syst_rls_audit_static_only_20261031
--   z_syst_rls_audit_refine_write_scope_20261031
--   z_syst_rls_audit_per_policy_scope_20261031
--   z_syst_rls_audit_final_shape_20261031
--   z_syst_fix_app_users_privilege_escalation_20261031
--
-- Two layers:
--   A) STATIC  - catalogue checks (function zsyst_qa.rls_audit())
--   B) DYNAMIC - impersonation checks that must run at top level, because
--                PostgreSQL forbids SET ROLE inside a SECURITY DEFINER function
--                (error 42501: cannot set parameter "role" within
--                security-definer function).
--
-- Run A with:      select * from zsyst_qa.rls_audit();
-- Run B as shown below, always inside BEGIN ... ROLLBACK so nothing persists.
-- ============================================================================


-- ---------------------------------------------------------------------------
-- A) STATIC CHECKS  (read-only, safe to run any time)
-- ---------------------------------------------------------------------------
select check_name, passed, detail
  from zsyst_qa.rls_audit()
 order by check_name;

-- Expected: every row passed = true.
--   1_all_tables_have_rls                 88 tables, 0 without RLS
--   2_every_table_has_a_policy            0 tables with RLS but zero policies
--   3_no_policy_targets_anon              0 policies granted to anon
--   4_every_insert_policy_scopes_business_id
--                                         THE load-bearing check. A policy that
--                                         only verifies id = auth.uid() is NOT
--                                         tenant isolation - that hole let any
--                                         user self-provision into another
--                                         tenant as owner (fixed 2026-10-31).
--   5_tables_without_client_insert        app_users (intentional: provisioning)
--   6_app_users_self_edit_is_guarded      trigger present
--   7_tenant_tables_scoped_by_business_id 84 / 84


-- ---------------------------------------------------------------------------
-- B1) DYNAMIC: an authenticated identity with no app_users row sees nothing
-- ---------------------------------------------------------------------------
begin;
set local role authenticated;
select set_config('request.jwt.claims',
                  json_build_object('sub','00000000-0000-0000-0000-0000000000aa',
                                    'role','authenticated')::text, true);

select 'app_users'          as tbl, count(*) as visible from public.app_users
union all select 'products',          count(*) from public.products
union all select 'sales',             count(*) from public.sales
union all select 'businesses',        count(*) from public.businesses
union all select 'subscription_plans',count(*) from public.subscription_plans
order by 1;
rollback;
-- Expected (2026-10-31): app_users 0, products 0, sales 0, businesses 0,
--                        subscription_plans 6   <-- public catalogue by design


-- ---------------------------------------------------------------------------
-- B1b) DYNAMIC: self-provisioning and self-escalation must be rejected
--      (run with a real user JWT against PostgREST, not in SQL - see doc 11)
--
--   POST  /rest/v1/app_users   {"id":"<own uid>","business_id":<other>,"role":"owner"}
--        -> expect 403 42501 new row violates row-level security policy
--   PATCH /rest/v1/app_users?id=eq.<own uid>   {"role":"owner"}
--        -> expect 403 app_users.role may only be changed by an administrator
--   PATCH /rest/v1/app_users?id=eq.<own uid>   {"business_id":<other>}
--        -> expect 403 app_users.business_id is immutable from the client
--   PATCH /rest/v1/app_users?id=eq.<own uid>   {"name":"new name"}
--        -> expect 200  (must keep working)


-- ---------------------------------------------------------------------------
-- B2) DYNAMIC: cross-tenant INSERT must be rejected (expects ERROR 42501)
-- ---------------------------------------------------------------------------
begin;
set local role authenticated;
select set_config('request.jwt.claims',
                  json_build_object('sub','00000000-0000-0000-0000-0000000000aa',
                                    'role','authenticated',
                                    'business_id','999999')::text, true);
insert into public.products (business_id, product_name)
values (999999, 'zsyst-qa-should-not-exist');
rollback;
-- Expected: ERROR 42501 new row violates row-level security policy for table
-- "products".  An error here IS the pass condition; a successful insert is a
-- policy hole and must be investigated before any tenant is onboarded.
