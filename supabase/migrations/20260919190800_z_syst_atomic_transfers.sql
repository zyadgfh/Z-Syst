-- Enterprise inventory transfer + atomic purchase return support
alter table public.stock_transfers
  add column if not exists idempotency_key text,
  add column if not exists completed_at timestamptz;

create unique index if not exists stock_transfers_business_idempotency_key_uidx
  on public.stock_transfers(business_id,idempotency_key)
  where idempotency_key is not null;

create or replace function private.post_stock_transfer(
  p_from_warehouse_id bigint,
  p_to_warehouse_id bigint,
  p_product_id bigint,
  p_quantity integer,
  p_notes text default null,
  p_idempotency_key text default null
) returns public.stock_transfers
language plpgsql
security definer
set search_path = public, private, pg_temp
as $$
declare
  v_business_id bigint := private.current_business_id();
  v_user_id uuid := auth.uid();
  v_transfer public.stock_transfers;
  v_from_qty integer;
  v_stock public.stocks;
begin
  if v_business_id is null then raise exception 'No active business context'; end if;
  if p_from_warehouse_id = p_to_warehouse_id then raise exception 'Source and destination warehouses must differ'; end if;
  if p_quantity <= 0 then raise exception 'Transfer quantity must be positive'; end if;
  if p_idempotency_key is not null then
    select * into v_transfer from public.stock_transfers
    where business_id=v_business_id and idempotency_key=p_idempotency_key limit 1;
    if found then return v_transfer; end if;
  end if;
  if not exists (select 1 from public.warehouses where id=p_from_warehouse_id and business_id=v_business_id and is_active)
     or not exists (select 1 from public.warehouses where id=p_to_warehouse_id and business_id=v_business_id and is_active) then
    raise exception 'Invalid warehouse for current business';
  end if;
  if not exists (select 1 from public.products where id=p_product_id and business_id=v_business_id) then
    raise exception 'Invalid product for current business';
  end if;
  select ws.quantity into v_from_qty from public.warehouse_stocks ws
  where ws.business_id=v_business_id and ws.warehouse_id=p_from_warehouse_id and ws.product_id=p_product_id
  for update;
  if coalesce(v_from_qty,0) < p_quantity then raise exception 'Insufficient source warehouse stock'; end if;
  update public.warehouse_stocks set quantity=quantity-p_quantity,updated_at=now()
  where business_id=v_business_id and warehouse_id=p_from_warehouse_id and product_id=p_product_id;
  insert into public.warehouse_stocks(business_id,warehouse_id,product_id,quantity)
  values(v_business_id,p_to_warehouse_id,p_product_id,p_quantity)
  on conflict (business_id,warehouse_id,product_id)
  do update set quantity=public.warehouse_stocks.quantity+excluded.quantity,updated_at=now();
  select * into v_stock from public.stocks
  where business_id=v_business_id and product_id=p_product_id
  order by expire_date nulls last,id limit 1 for update;
  insert into public.stock_transfers(
    business_id,from_warehouse_id,to_warehouse_id,product_id,quantity,status,notes,user_id,idempotency_key,completed_at
  ) values(v_business_id,p_from_warehouse_id,p_to_warehouse_id,p_product_id,p_quantity,'completed',
           p_notes,v_user_id,p_idempotency_key,now())
  returning * into v_transfer;
  insert into public.stock_movements(
    business_id,product_id,warehouse_id,stock_id,movement_type,quantity,reference_type,reference_id,
    batch_no,expire_date,unit_cost,notes,created_by
  )
  select v_business_id,p_product_id,p_from_warehouse_id,v_stock.id,'transfer_out',-p_quantity,
         'stock_transfer',v_transfer.id,v_stock.batch_no,v_stock.expire_date,0,p_notes,v_user_id
  where v_stock.id is not null;
  insert into public.stock_movements(
    business_id,product_id,warehouse_id,stock_id,movement_type,quantity,reference_type,reference_id,
    batch_no,expire_date,unit_cost,notes,created_by
  )
  select v_business_id,p_product_id,p_to_warehouse_id,v_stock.id,'transfer_in',p_quantity,
         'stock_transfer',v_transfer.id,v_stock.batch_no,v_stock.expire_date,0,p_notes,v_user_id
  where v_stock.id is not null;
  insert into public.audit_logs(
    business_id,actor_user_id,action,model_type,model_id,description,new_values
  ) values(v_business_id,v_user_id,'stock_transfer.posted','stock_transfer',v_transfer.id,
           'Atomic warehouse stock transfer',
           jsonb_build_object('from_warehouse_id',p_from_warehouse_id,'to_warehouse_id',p_to_warehouse_id,
                              'product_id',p_product_id,'quantity',p_quantity));
  return v_transfer;
end;
$$;
revoke all on function private.post_stock_transfer(bigint,bigint,bigint,integer,text,text) from public,anon;
grant execute on function private.post_stock_transfer(bigint,bigint,bigint,integer,text,text) to authenticated;
