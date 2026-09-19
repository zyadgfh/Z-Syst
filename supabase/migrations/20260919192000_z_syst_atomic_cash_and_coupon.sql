-- Atomic cash register operations and coupon redemption
create unique index if not exists coupon_redemptions_business_coupon_sale_uidx
  on public.coupon_redemptions(business_id,coupon_id,sale_id);
create index if not exists coupon_redemptions_business_coupon_party_idx
  on public.coupon_redemptions(business_id,coupon_id,party_id);
create index if not exists cash_register_transactions_business_register_created_idx
  on public.cash_register_transactions(business_id,register_id,created_at);

create or replace function private.open_cash_register(
  p_branch_id bigint,p_opening_balance numeric default 0,p_notes text default null
) returns public.cash_registers
language plpgsql security definer set search_path=public,private,pg_temp
as $$
declare v_business_id bigint:=private.current_business_id(); v_user uuid:=auth.uid(); v public.cash_registers;
begin
  if v_business_id is null then raise exception 'No active business context'; end if;
  if p_opening_balance < 0 then raise exception 'Opening balance cannot be negative'; end if;
  if p_branch_id is not null and not exists(select 1 from public.branches where id=p_branch_id and business_id=v_business_id and is_active)
    then raise exception 'Invalid branch'; end if;
  if exists(select 1 from public.cash_registers where business_id=v_business_id and branch_id is not distinct from p_branch_id and status='open')
    then raise exception 'An open cash register already exists for this branch'; end if;
  insert into public.cash_registers(business_id,branch_id,opened_by,status,opening_balance,notes)
  values(v_business_id,p_branch_id,v_user,'open',p_opening_balance,p_notes) returning * into v;
  insert into public.cash_register_transactions(business_id,register_id,type,amount,notes,created_by)
  values(v_business_id,v.id,'opening',p_opening_balance,p_notes,v_user);
  return v;
end;
$$;

create or replace function private.post_cash_register_transaction(
  p_register_id bigint,p_type text,p_amount numeric,p_reference_type text default null,
  p_reference_id bigint default null,p_notes text default null
) returns public.cash_register_transactions
language plpgsql security definer set search_path=public,private,pg_temp
as $$
declare v_business_id bigint:=private.current_business_id(); v_user uuid:=auth.uid(); v_register public.cash_registers; v_tx public.cash_register_transactions;
begin
  if v_business_id is null then raise exception 'No active business context'; end if;
  if p_amount <= 0 then raise exception 'Transaction amount must be positive'; end if;
  if p_type not in ('cash_in','cash_out','sale','refund') then raise exception 'Invalid cash register transaction type'; end if;
  select * into v_register from public.cash_registers where id=p_register_id and business_id=v_business_id for update;
  if v_register.id is null or v_register.status <> 'open' then raise exception 'Cash register is not open'; end if;
  insert into public.cash_register_transactions(business_id,register_id,type,amount,reference_type,reference_id,notes,created_by)
  values(v_business_id,p_register_id,p_type,p_amount,p_reference_type,p_reference_id,p_notes,v_user) returning * into v_tx;
  update public.cash_registers
  set cash_in=cash_in+case when p_type='cash_in' then p_amount else 0 end,
      cash_out=cash_out+case when p_type='cash_out' then p_amount else 0 end,
      cash_sales=cash_sales+case when p_type='sale' then p_amount else 0 end,
      cash_refunds=cash_refunds+case when p_type='refund' then p_amount else 0 end,
      updated_at=now()
  where id=p_register_id and business_id=v_business_id;
  return v_tx;
end;
$$;

create or replace function private.close_cash_register(
  p_register_id bigint,p_actual_closing_balance numeric,p_notes text default null
) returns public.cash_registers
language plpgsql security definer set search_path=public,private,pg_temp
as $$
declare v_business_id bigint:=private.current_business_id(); v_user uuid:=auth.uid(); v public.cash_registers; v_expected numeric;
begin
  if v_business_id is null then raise exception 'No active business context'; end if;
  if p_actual_closing_balance < 0 then raise exception 'Closing balance cannot be negative'; end if;
  select * into v from public.cash_registers where id=p_register_id and business_id=v_business_id for update;
  if v.id is null or v.status <> 'open' then raise exception 'Cash register is not open'; end if;
  v_expected := v.opening_balance + v.cash_sales - v.cash_refunds + v.cash_in - v.cash_out;
  update public.cash_registers
  set status='closed',closed_by=v_user,closed_at=now(),closing_balance=p_actual_closing_balance,
      expected_balance=v_expected,notes=coalesce(p_notes,notes),updated_at=now()
  where id=p_register_id and business_id=v_business_id returning * into v;
  insert into public.cash_register_transactions(business_id,register_id,type,amount,notes,created_by)
  values(v_business_id,v.id,'closing',p_actual_closing_balance,p_notes,v_user);
  return v;
end;
$$;

create or replace function private.redeem_coupon(
  p_coupon_code text,p_sale_id bigint,p_order_amount numeric,p_party_id bigint default null
) returns public.coupon_redemptions
language plpgsql security definer set search_path=public,private,pg_temp
as $$
declare v_business_id bigint:=private.current_business_id(); v_user uuid:=auth.uid();
v_coupon public.coupons; v_sale public.sales; v_existing public.coupon_redemptions; v_discount numeric;
begin
  if v_business_id is null then raise exception 'No active business context'; end if;
  if p_order_amount < 0 then raise exception 'Order amount cannot be negative'; end if;
  select * into v_sale from public.sales where id=p_sale_id and business_id=v_business_id for update;
  if v_sale.id is null then raise exception 'Sale not found in current business'; end if;
  select * into v_coupon from public.coupons where business_id=v_business_id and upper(code)=upper(trim(p_coupon_code)) for update;
  if v_coupon.id is null then raise exception 'Coupon not found'; end if;
  select * into v_existing from public.coupon_redemptions where business_id=v_business_id and coupon_id=v_coupon.id and sale_id=p_sale_id limit 1;
  if v_existing.id is not null then return v_existing; end if;
  if not v_coupon.is_active then raise exception 'Coupon is inactive'; end if;
  if v_coupon.start_at is not null and now() < v_coupon.start_at then raise exception 'Coupon is not active yet'; end if;
  if v_coupon.end_at is not null and now() > v_coupon.end_at then raise exception 'Coupon has expired'; end if;
  if p_order_amount < v_coupon.min_order_amount then raise exception 'Minimum order amount not met'; end if;
  if v_coupon.usage_limit is not null and v_coupon.usage_count >= v_coupon.usage_limit then raise exception 'Coupon usage limit reached'; end if;
  if p_party_id is not null and v_coupon.per_customer_limit is not null and
     (select count(*) from public.coupon_redemptions where business_id=v_business_id and coupon_id=v_coupon.id and party_id=p_party_id) >= v_coupon.per_customer_limit
    then raise exception 'Customer coupon limit reached'; end if;
  if v_coupon.discount_type='percentage' then v_discount:=round(p_order_amount*v_coupon.discount_value/100,2);
  else v_discount:=v_coupon.discount_value; end if;
  if v_coupon.max_discount_amount is not null then v_discount:=least(v_discount,v_coupon.max_discount_amount); end if;
  v_discount:=greatest(0,least(v_discount,p_order_amount));
  insert into public.coupon_redemptions(business_id,coupon_id,sale_id,party_id,discount_amount,created_by)
  values(v_business_id,v_coupon.id,p_sale_id,p_party_id,v_discount,v_user) returning * into v_existing;
  update public.coupons set usage_count=usage_count+1,updated_at=now() where id=v_coupon.id and business_id=v_business_id;
  return v_existing;
end;
$$;

revoke all on function private.open_cash_register(bigint,numeric,text) from public,anon;
revoke all on function private.post_cash_register_transaction(bigint,text,numeric,text,bigint,text) from public,anon;
revoke all on function private.close_cash_register(bigint,numeric,text) from public,anon;
revoke all on function private.redeem_coupon(text,bigint,numeric,bigint) from public,anon;
grant execute on function private.open_cash_register(bigint,numeric,text) to authenticated;
grant execute on function private.post_cash_register_transaction(bigint,text,numeric,text,bigint,text) to authenticated;
grant execute on function private.close_cash_register(bigint,numeric,text) to authenticated;
grant execute on function private.redeem_coupon(text,bigint,numeric,bigint) to authenticated;
