-- Atomic purchase return posting
create or replace function private.post_purchase_return(
  p_purchase_id bigint,
  p_product_id bigint,
  p_quantity integer,
  p_stock_id bigint default null,
  p_refund_amount numeric default 0,
  p_notes text default null
) returns public.purchase_returns
language plpgsql
security definer
set search_path = public, private, pg_temp
as $$
declare
  v_business_id bigint := private.current_business_id();
  v_user_id uuid := auth.uid();
  v_return public.purchase_returns;
  v_stock public.stocks;
  v_warehouse_id bigint;
begin
  if v_business_id is null then raise exception 'No active business context'; end if;
  if p_quantity <= 0 then raise exception 'Return quantity must be positive'; end if;
  if p_refund_amount < 0 then raise exception 'Refund amount cannot be negative'; end if;
  if not exists (select 1 from public.purchases p where p.id=p_purchase_id and p.business_id=v_business_id) then
    raise exception 'Purchase not found in current business';
  end if;
  if not exists (select 1 from public.purchase_items pi
                 where pi.purchase_id=p_purchase_id and pi.product_id=p_product_id and pi.business_id=v_business_id) then
    raise exception 'Product is not part of purchase';
  end if;
  select * into v_stock from public.stocks
  where business_id=v_business_id and product_id=p_product_id
    and (p_stock_id is null or id=p_stock_id)
  order by expire_date nulls last,id limit 1 for update;
  if v_stock.id is null then raise exception 'Stock batch not found'; end if;
  if v_stock.product_stock < p_quantity then raise exception 'Insufficient stock for purchase return'; end if;
  select w.id into v_warehouse_id
  from public.warehouses w
  join public.warehouse_stocks ws on ws.warehouse_id=w.id and ws.business_id=w.business_id
  where w.business_id=v_business_id and ws.product_id=p_product_id and ws.quantity >= p_quantity
  order by w.is_default desc,w.id limit 1 for update;
  if v_warehouse_id is null then raise exception 'No warehouse has sufficient stock for return'; end if;
  insert into public.purchase_returns(business_id,purchase_id,invoice_no,return_date)
  select v_business_id,purchase_id,invoice_number,now()
  from public.purchases where id=p_purchase_id and business_id=v_business_id
  returning * into v_return;
  update public.stocks set product_stock=product_stock-p_quantity,updated_at=now()
  where id=v_stock.id and business_id=v_business_id;
  update public.warehouse_stocks set quantity=quantity-p_quantity,updated_at=now()
  where business_id=v_business_id and warehouse_id=v_warehouse_id and product_id=p_product_id;
  insert into public.purchase_return_items(
    business_id,purchase_return_id,purchase_item_id,product_id,stock_id,quantity,refund_amount,batch_no,expire_date
  )
  select v_business_id,v_return.id,pi.id,p_product_id,v_stock.id,p_quantity,p_refund_amount,v_stock.batch_no,v_stock.expire_date
  from public.purchase_items pi where pi.purchase_id=p_purchase_id and pi.product_id=p_product_id
  order by pi.id limit 1;
  insert into public.stock_movements(
    business_id,product_id,warehouse_id,stock_id,movement_type,quantity,reference_type,reference_id,
    batch_no,expire_date,unit_cost,notes,created_by
  ) values(v_business_id,p_product_id,v_warehouse_id,v_stock.id,'purchase_return',-p_quantity,
           'purchase_return',v_return.id,v_stock.batch_no,v_stock.expire_date,0,p_notes,v_user_id);
  insert into public.audit_logs(
    business_id,actor_user_id,action,model_type,model_id,description,new_values
  ) values(v_business_id,v_user_id,'purchase_return.posted','purchase_return',v_return.id,
           'Atomic purchase return posted',
           jsonb_build_object('purchase_id',p_purchase_id,'product_id',p_product_id,'quantity',p_quantity,
                              'stock_id',v_stock.id,'warehouse_id',v_warehouse_id,'refund_amount',p_refund_amount));
  return v_return;
end;
$$;
revoke all on function private.post_purchase_return(bigint,bigint,integer,bigint,numeric,text) from public,anon;
grant execute on function private.post_purchase_return(bigint,bigint,integer,bigint,numeric,text) to authenticated;
