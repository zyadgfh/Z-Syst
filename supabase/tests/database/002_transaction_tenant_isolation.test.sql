begin;
select plan(10);

select has_table('public','businesses','tenant root exists');
select has_table('public','app_users','auth-to-tenant membership exists');
select has_table('public','sales','sales table exists');
select has_table('public','purchases','purchases table exists');
select has_table('public','cash_registers','cash registers exist');
select has_table('public','cash_register_transactions','cash register ledger exists');

select ok(
  (select count(*) from pg_policies p where p.schemaname='public' and p.tablename='financial_transactions' and p.cmd='SELECT') > 0,
  'financial ledger has tenant SELECT policy'
);
select ok(
  (select count(*) from pg_policies p where p.schemaname='public' and p.tablename='financial_transactions' and p.cmd='INSERT') > 0,
  'financial ledger has tenant INSERT policy'
);
select is(
  has_function_privilege('anon','public.api_post_purchase_return_financial(bigint,bigint,integer,bigint,numeric,text,bigint)','execute'),
  false,
  'anonymous role cannot post purchase refunds'
);
select ok(
  (select count(*) from pg_policies p where p.schemaname='public' and p.tablename='financial_transactions') >= 2,
  'financial ledger has explicit RLS policies'
);

select * from finish();
rollback;