begin;
select plan(12);

select has_table('public','financial_transactions','financial ledger exists');
select has_column('public','financial_transactions','business_id','ledger is tenant-scoped');
select has_column('public','financial_transactions','payment_method','ledger records payment method');
select has_column('public','cash_register_transactions','reference_id','cash transaction references source');
select ok(to_regprocedure('public.api_post_sale_financial(bigint,bigint,bigint,text,text,numeric,numeric,numeric,jsonb,text,bigint)') is not null, 'sale financial API exists');
select ok(to_regprocedure('public.api_receive_purchase_financial(bigint,bigint,bigint,text,text,numeric,numeric,numeric,jsonb,text,bigint)') is not null, 'purchase financial API exists');
select ok(to_regprocedure('public.api_post_sale_return_financial(bigint,bigint,text,jsonb,text,bigint)') is not null, 'sale return financial API exists');
select ok(to_regprocedure('public.api_post_purchase_return_financial(bigint,bigint,integer,bigint,numeric,text,bigint)') is not null, 'purchase return financial API exists');

select is(
  (select relrowsecurity from pg_class where oid='public.financial_transactions'::regclass),
  true,
  'financial ledger has RLS'
);
select ok(
  has_table('public','cash_registers') and has_table('public','cash_register_transactions'),
  'cash register primitives exist'
);
select ok(
  has_index('public','financial_transactions','financial_transactions_business_created_idx'),
  'financial ledger has tenant/time index'
);
select is(
  has_function_privilege('anon','public.api_post_sale_financial(bigint,bigint,bigint,text,text,numeric,numeric,numeric,jsonb,text,bigint)','execute'),
  false,
  'anonymous role cannot post financially coupled sale'
);

select * from finish();
rollback;