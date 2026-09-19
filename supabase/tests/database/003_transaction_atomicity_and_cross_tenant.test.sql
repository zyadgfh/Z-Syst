begin;

select plan(12);

-- Fixture setup runs before switching to the authenticated role.
insert into public.businesses(company_name) values ('TEST BUSINESS A'), ('TEST BUSINESS B');

do $$
declare
  a bigint; b bigint; u1 uuid:=gen_random_uuid(); u2 uuid:=gen_random_uuid();
begin
  select id into a from public.businesses where company_name='TEST BUSINESS A' order by id desc limit 1;
  select id into b from public.businesses where company_name='TEST BUSINESS B' order by id desc limit 1;

  insert into public.app_users(id,business_id,name,role,status)
  values(u1,a,'Test User A','admin','active'),(u2,b,'Test User B','admin','active');

  insert into public.warehouses(business_id,name,code,is_default,is_active)
  values(a,'Test Warehouse A','TWA',true,true);

  insert into public.products(
    business_id,product_name,purchase_without_tax,purchase_with_tax,profit_percent,
    sales_price,alert_qty,wholesale_price,tax_type
  ) values(a,'Test Medicine A',5,5,20,10,1,8,'none');

  insert into public.cash_registers(business_id,opened_by,status,opening_balance)
  values(a,u1,'open',100);
end $$;

set local role authenticated;

do $$
declare
  a bigint; u1 uuid; w bigint; p bigint; sale_id bigint;
  before_sales bigint; before_fin bigint; before_cash bigint;
begin
  select id into a from public.businesses where company_name='TEST BUSINESS A' order by id desc limit 1;
  select id into u1 from public.app_users where business_id=a limit 1;
  select id into w from public.warehouses where business_id=a limit 1;
  select id into p from public.products where business_id=a limit 1;
  insert into public.stocks(business_id,product_id,product_stock,batch_no) values(a,p,10,'TEST-BATCH');
  insert into public.warehouse_stocks(business_id,warehouse_id,product_id,quantity) values(a,w,p,10);

  perform set_config('request.jwt.claim.sub',u1::text,true);

  before_sales:=(select count(*) from public.sales where business_id=a);
  before_fin:=(select count(*) from public.financial_transactions where business_id=a);
  before_cash:=(select count(*) from public.cash_register_transactions where business_id=a);

  select public.api_post_sale_financial(
    a,null,w,'TEST-INV-1','cash',20,0,0,
    jsonb_build_array(jsonb_build_object('product_id',p,'quantity',2,'unit_price',10)),
    'atomic-test-1',null
  ) into sale_id;

  if sale_id is null then raise exception 'sale id missing'; end if;

  if (select count(*) from public.sales where business_id=a) <> before_sales+1 then raise exception 'sale not committed'; end if;
  if (select count(*) from public.financial_transactions where business_id=a) <> before_fin+1 then raise exception 'financial ledger not committed'; end if;
  if (select count(*) from public.cash_register_transactions where business_id=a) <> before_cash+1 then raise exception 'cash ledger not committed'; end if;
end $$;

select ok(true,'sale + financial ledger + cash transaction commit in one transaction');

select throws_ok(
  $$select public.api_post_sale_financial(
    (select id from public.businesses where company_name='TEST BUSINESS A' order by id desc limit 1),
    null,
    (select id from public.warehouses where business_id=(select id from public.businesses where company_name='TEST BUSINESS A' order by id desc limit 1) limit 1),
    'TEST-INV-FAIL','cash',1000,0,0,
    jsonb_build_array(jsonb_build_object('product_id',(select id from public.products where business_id=(select id from public.businesses where company_name='TEST BUSINESS A' order by id desc limit 1) limit 1),'quantity',999,'unit_price',10)),
    'atomic-test-fail',null)$$,
  'insufficient warehouse stock',
  'oversell is rejected atomically'
);

select is(
  (select count(*) from public.sales where invoice_number='TEST-INV-FAIL'),
  0::bigint,
  'failed sale leaves no sale row'
);

select is(
  (select count(*) from public.financial_transactions where reference_type='sale' and metadata->>'idempotency_key'='atomic-test-fail'),
  0::bigint,
  'failed sale leaves no financial ledger row'
);

select is(
  (select count(*) from public.cash_register_transactions where reference_type='sale' and reference_id not in (select id from public.sales where invoice_number='TEST-INV-FAIL')),
  1::bigint,
  'failed sale leaves no extra cash transaction'
);

-- Cross-tenant RLS: user A must not see user B's financial rows.
do $$
declare b bigint; u2 uuid;
begin
  select id into b from public.businesses where company_name='TEST BUSINESS B' order by id desc limit 1;
  select id into u2 from public.app_users where business_id=b limit 1;
  insert into public.financial_transactions(business_id,transaction_type,direction,payment_method,amount,metadata)
  values(b,'test','in','cash',50,'{"tenant_fixture":true}');
  perform set_config('request.jwt.claim.sub',u2::text,true);
end $$;

select is(
  (select count(*) from public.financial_transactions),
  1::bigint,
  'authenticated tenant sees only its own financial rows'
);

do $$
declare a bigint; u1 uuid;
begin
  select id into a from public.businesses where company_name='TEST BUSINESS A' order by id desc limit 1;
  select id into u1 from public.app_users where business_id=a limit 1;
  perform set_config('request.jwt.claim.sub',u1::text,true);
end $$;

select is(
  (select count(*) from public.financial_transactions),
  1::bigint,
  'switching back to tenant A still excludes tenant B'
);

select ok(
  (select count(*) from public.sales where business_id=(select id from public.businesses where company_name='TEST BUSINESS A' order by id desc limit 1)) = 1,
  'tenant A sales remain isolated and intact'
);

select ok(
  (select count(*) from public.warehouse_stocks where business_id=(select id from public.businesses where company_name='TEST BUSINESS A' order by id desc limit 1)) = 1,
  'tenant A inventory remains isolated'
);

select ok(
  (select count(*) from public.cash_register_transactions where business_id=(select id from public.businesses where company_name='TEST BUSINESS A' order by id desc limit 1)) = 1,
  'tenant A cash flow remains isolated'
);

select ok(
  (select count(*) from public.financial_transactions where business_id=(select id from public.businesses where company_name='TEST BUSINESS A' order by id desc limit 1)) = 1,
  'tenant A financial flow remains isolated'
);

select * from finish();
rollback;