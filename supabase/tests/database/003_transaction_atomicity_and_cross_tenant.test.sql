begin;

select plan(13);

insert into public.businesses(company_name) values ('TEST BUSINESS A'), ('TEST BUSINESS B');
create temp table tx_fixture(a bigint,b bigint,w bigint,p bigint,u1 uuid,u2 uuid) on commit drop;

do $$
declare a bigint; b bigint; w bigint; p bigint; u1 uuid:=gen_random_uuid(); u2 uuid:=gen_random_uuid();
begin
  select f.a into a from tx_fixture f limit 1;
  select f.b into b from tx_fixture f limit 1;
  insert into auth.users(id,aud,role,email,created_at,updated_at,email_confirmed_at)
  values
    (u1,'authenticated','authenticated','tx-a-'||u1||'@example.test',now(),now(),now()),
    (u2,'authenticated','authenticated','tx-b-'||u2||'@example.test',now(),now(),now());
  insert into public.app_users(id,business_id,name,role,status)
  values(u1,a,'Test User A','admin','active'),(u2,b,'Test User B','admin','active');
  insert into public.warehouses(business_id,name,code,is_default,is_active)
  values(a,'Test Warehouse A','TWA',true,true) returning id into w;
  insert into public.products(business_id,product_name,purchase_without_tax,purchase_with_tax,profit_percent,sales_price,alert_qty,wholesale_price,tax_type)
  values(a,'Test Medicine A',5,5,20,10,1,8,'none') returning id into p;
  insert into tx_fixture values(a,b,w,p,u1,u2);
  insert into public.cash_registers(business_id,opened_by,status,opening_balance)
  values(a,u1,'open',100);
end $$;

set local role authenticated;

do $$
declare a bigint; u1 uuid; w bigint; p bigint; sale_id bigint;
begin
  select id into a from public.businesses where company_name='TEST BUSINESS A' order by id desc limit 1;
  select id into u1 from public.app_users where business_id=a limit 1;
  select f.w into w from tx_fixture f limit 1;
  select f.p into p from tx_fixture f limit 1;
  insert into public.stocks(business_id,product_id,product_stock,batch_no) values(a,p,10,'TEST-BATCH');
  insert into public.warehouse_stocks(business_id,warehouse_id,product_id,quantity) values(a,w,p,10);
  perform set_config('request.jwt.claims',json_build_object('sub',u1::text,'role','authenticated')::text,true);
  select public.api_post_sale_financial(a,null,w,'TEST-INV-1','cash',20,0,0,
    jsonb_build_array(jsonb_build_object('product_id',p,'quantity',2,'unit_price',10)),'atomic-test-1',null) into sale_id;
  if sale_id is null then raise exception 'sale id missing'; end if;
  if (select count(*) from public.sales where business_id=a) <> 1 then raise exception 'sale not committed'; end if;
  if (select count(*) from public.financial_transactions where business_id=a) <> 1 then raise exception 'financial ledger not committed'; end if;
  if (select count(*) from public.cash_register_transactions where business_id=a) <> 1 then raise exception 'cash ledger not committed'; end if;
  if (select quantity from public.warehouse_stocks where business_id=a and warehouse_id=w and product_id=p) <> 8 then raise exception 'warehouse stock mismatch'; end if;
end $$;

select ok(true,'sale + financial ledger + cash transaction commit atomically');

select throws_ok(
  $$select public.api_post_sale_financial(
    (select a from tx_fixture),null,
    (select id from public.warehouses where business_id=(select id from public.businesses where company_name='TEST BUSINESS A' order by id desc limit 1) limit 1),
    'TEST-INV-FAIL','cash',1000,0,0,
    jsonb_build_array(jsonb_build_object('product_id',(select id from public.products where business_id=(select id from public.businesses where company_name='TEST BUSINESS A' order by id desc limit 1) limit 1),'quantity',999,'unit_price',10)),
    'atomic-test-fail',null)$$,
  'insufficient warehouse stock',
  'oversell is rejected atomically'
);

select is((select count(*) from public.sales where invoice_number='TEST-INV-FAIL'),0::bigint,'failed sale leaves no sale row');
select is((select count(*) from public.financial_transactions where reference_type='sale' and metadata->>'idempotency_key'='atomic-test-fail'),0::bigint,'failed sale leaves no financial row');

do $
declare a bigint; u1 uuid; w bigint; p bigint; first_sale bigint; second_sale bigint;
begin
  select id into a from public.businesses where company_name='TEST BUSINESS A' order by id desc limit 1;
  select id into u1 from public.app_users where business_id=a limit 1;
  select id into w from public.warehouses where business_id=a limit 1;
  select id into p from public.products where business_id=a limit 1;
  perform set_config('request.jwt.claims',json_build_object('sub',u1::text,'role','authenticated')::text,true);
  select public.api_post_sale_financial(a,null,w,'TEST-INV-IDEMP','cash',10,0,0,
    jsonb_build_array(jsonb_build_object('product_id',p,'quantity',1,'unit_price',10)),'same-key',null) into first_sale;
  select public.api_post_sale_financial(a,null,w,'TEST-INV-IDEMP','cash',10,0,0,
    jsonb_build_array(jsonb_build_object('product_id',p,'quantity',1,'unit_price',10)),'same-key',null) into second_sale;
  if first_sale <> second_sale then raise exception 'idempotency returned different sale ids'; end if;
end $;

select is((select count(*) from public.sales where idempotency_key='same-key'),1::bigint,'sale idempotency prevents duplicate sale');
select is((select count(*) from public.financial_transactions where transaction_type='sale' and reference_type='sale' and metadata->>'idempotency_key'='same-key'),1::bigint,'sale idempotency prevents duplicate financial posting');
select is((select count(*) from public.cash_register_transactions where reference_type='sale' and reference_id=(select min(id) from public.sales where idempotency_key='same-key')),1::bigint,'sale idempotency prevents duplicate cash posting');

do $
declare a bigint; u1 uuid; w bigint; p bigint; before_stock integer;
begin
  select id into a from public.businesses where company_name='TEST BUSINESS A' order by id desc limit 1;
  select id into u1 from public.app_users where business_id=a limit 1;
  select id into w from public.warehouses where business_id=a limit 1;
  select id into p from public.products where business_id=a limit 1;
  select product_stock into before_stock from public.stocks where business_id=a and product_id=p limit 1;
  update public.cash_registers set status='closed',closed_at=now() where business_id=a;
  perform set_config('request.jwt.claims',json_build_object('sub',u1::text,'role','authenticated')::text,true);
  begin
    perform public.api_post_sale_financial(a,null,w,'TEST-INV-NO-REGISTER','cash',10,0,0,
      jsonb_build_array(jsonb_build_object('product_id',p,'quantity',1,'unit_price',10)),'no-register',null);
    raise exception 'expected missing register failure';
  exception when others then
    if sqlerrm not like '%No open cash register%' then raise; end if;
  end;
  if (select count(*) from public.sales where invoice_number='TEST-INV-NO-REGISTER') <> 0 then raise exception 'sale committed without register'; end if;
  if (select product_stock from public.stocks where business_id=a and product_id=p limit 1) <> before_stock then raise exception 'stock changed after rollback'; end if;
end $;

select ok(true,'cash sale without open register rolls back sale and stock atomically');

insert into public.cash_registers(business_id,opened_by,status,opening_balance)
select x.a,u.id,'open',0
from (select id as a from public.businesses where company_name='TEST BUSINESS A' order by id desc limit 1) x
join lateral (select id from public.app_users where business_id=x.a limit 1) u on true;


do $$
declare b bigint; u2 uuid;
begin
  select id into b from public.businesses where company_name='TEST BUSINESS B' order by id desc limit 1;
  select id into u2 from public.app_users where business_id=b limit 1;
  insert into public.financial_transactions(business_id,transaction_type,direction,payment_method,amount,metadata)
  values(b,'test','in','cash',50,'{"tenant_fixture":true}');
  perform set_config('request.jwt.claims',json_build_object('sub',u2::text,'role','authenticated')::text,true);
end $$;

select is((select count(*) from public.financial_transactions),1::bigint,'tenant B sees only tenant B financial rows');

do $$
declare a bigint; u1 uuid;
begin
  select id into a from public.businesses where company_name='TEST BUSINESS A' order by id desc limit 1;
  select id into u1 from public.app_users where business_id=a limit 1;
  perform set_config('request.jwt.claims',json_build_object('sub',u1::text,'role','authenticated')::text,true);
end $$;

select is((select count(*) from public.financial_transactions),1::bigint,'tenant A sees only tenant A financial rows');
select ok((select count(*) from public.sales where business_id=(select id from public.businesses where company_name='TEST BUSINESS A' order by id desc limit 1))=1,'tenant A sales intact');
select ok((select count(*) from public.warehouse_stocks where business_id=(select id from public.businesses where company_name='TEST BUSINESS A' order by id desc limit 1))=1,'tenant A inventory intact');
select ok((select count(*) from public.cash_register_transactions where business_id=(select id from public.businesses where company_name='TEST BUSINESS A' order by id desc limit 1))=1,'tenant A cash flow isolated');
select ok((select count(*) from public.financial_transactions where business_id=(select id from public.businesses where company_name='TEST BUSINESS A' order by id desc limit 1))=1,'tenant A financial flow isolated');

select is(
  has_function_privilege('anon','public.api_post_sale_financial(bigint,bigint,bigint,text,text,numeric,numeric,numeric,jsonb,text,bigint)','execute'),
  false,
  'anonymous cannot execute financial sale API'
);

select * from finish();
rollback;