-- Post-deploy hardening for cash/coupon immutability and concurrent warehouse sales.
-- Applied after the original domain migrations; safe for already-provisioned environments.

revoke update, delete on public.cash_register_transactions from authenticated;
revoke update, delete on public.coupon_redemptions from authenticated;

drop policy if exists cash_register_transactions_tenant on public.cash_register_transactions;
create policy cash_register_transactions_tenant_select on public.cash_register_transactions for select to authenticated using (business_id=(select private.current_business_id()));
create policy cash_register_transactions_tenant_insert on public.cash_register_transactions for insert to authenticated with check (business_id=(select private.current_business_id()));

drop policy if exists coupon_redemptions_tenant on public.coupon_redemptions;
create policy coupon_redemptions_tenant_select on public.coupon_redemptions for select to authenticated using (business_id=(select private.current_business_id()));
create policy coupon_redemptions_tenant_insert on public.coupon_redemptions for insert to authenticated with check (business_id=(select private.current_business_id()));

-- The canonical private.post_sale definition is redeclared here so clean and
-- already-provisioned environments converge on the same concurrency-safe logic.
-- Warehouse availability is locked before validation and the deduction is
-- conditional, preventing concurrent oversell races.
create or replace function private.post_sale(
  p_business_id bigint,p_party_id bigint,p_warehouse_id bigint,p_invoice_number text,
  p_payment_type text,p_paid_amount numeric,p_discount_amount numeric,p_tax_amount numeric,
  p_items jsonb,p_idempotency_key text default null
) returns bigint
language plpgsql security definer set search_path=public,private,pg_temp
as $$
declare v_sale_id bigint; v_item jsonb; v_product bigint; v_qty int; v_remaining int;
v_stock record; v_take int; v_total numeric:=0; v_line numeric; v_user uuid:=auth.uid();
begin
 if p_business_id is distinct from private.current_business_id() then raise exception 'tenant mismatch'; end if;
 if p_idempotency_key is not null then select id into v_sale_id from sales where business_id=p_business_id and idempotency_key=p_idempotency_key; if v_sale_id is not null then return v_sale_id; end if; end if;
 if p_items is null or jsonb_typeof(p_items)<>'array' or jsonb_array_length(p_items)=0 then raise exception 'items required'; end if;
 if not exists(select 1 from warehouses w where w.id=p_warehouse_id and w.business_id=p_business_id and w.is_active) then raise exception 'warehouse unavailable'; end if;
 for v_item in select * from jsonb_array_elements(p_items) loop
  v_product:=(v_item->>'product_id')::bigint; v_qty:=(v_item->>'quantity')::int;
  if v_qty is null or v_qty<=0 then raise exception 'invalid quantity'; end if;
  if not exists(select 1 from products p where p.id=v_product and p.business_id=p_business_id) then raise exception 'product not found in tenant'; end if;
  perform 1 from warehouse_stocks ws where ws.business_id=p_business_id and ws.warehouse_id=p_warehouse_id and ws.product_id=v_product for update;
  if coalesce((select quantity from warehouse_stocks ws where ws.business_id=p_business_id and ws.warehouse_id=p_warehouse_id and ws.product_id=v_product),0)<v_qty then raise exception 'insufficient warehouse stock for product %',v_product; end if;
  v_line:=coalesce((v_item->>'unit_price')::numeric,(select sales_price from products where id=v_product));
  v_total:=v_total+(v_line*v_qty)-coalesce((v_item->>'discount_amount')::numeric,0)+coalesce((v_item->>'tax_amount')::numeric,0);
 end loop;
 v_total:=greatest(v_total-coalesce(p_discount_amount,0)+coalesce(p_tax_amount,0),0);
 insert into sales(business_id,party_id,user_id,discount_amount,due_amount,is_paid,tax_amount,paid_amount,total_amount,loss_profit,payment_type,invoice_number,sale_date,meta,idempotency_key)
 values(p_business_id,p_party_id,v_user,coalesce(p_discount_amount,0),greatest(v_total-coalesce(p_paid_amount,0),0),coalesce(p_paid_amount,0)>=v_total,coalesce(p_tax_amount,0),coalesce(p_paid_amount,0),v_total,0,p_payment_type,p_invoice_number,now(),jsonb_build_object('posted_by_rpc',true),p_idempotency_key) returning id into v_sale_id;
 for v_item in select * from jsonb_array_elements(p_items) loop
  v_product:=(v_item->>'product_id')::bigint; v_qty:=(v_item->>'quantity')::int;
  insert into sale_items(business_id,sale_id,product_id,quantity,unit_price,tax_amount,discount_amount,total_amount,batch_no)
  values(p_business_id,v_sale_id,v_product,v_qty,coalesce((v_item->>'unit_price')::numeric,(select sales_price from products where id=v_product)),coalesce((v_item->>'tax_amount')::numeric,0),coalesce((v_item->>'discount_amount')::numeric,0),(coalesce((v_item->>'unit_price')::numeric,(select sales_price from products where id=v_product))*v_qty)+coalesce((v_item->>'tax_amount')::numeric,0)-coalesce((v_item->>'discount_amount')::numeric,0),null);
  v_remaining:=v_qty;
  for v_stock in select s.* from stocks s where s.business_id=p_business_id and s.product_id=v_product and s.product_stock>0 and (s.expire_date is null or s.expire_date>=current_date) and not exists(select 1 from recall_events r where r.business_id=p_business_id and r.status in ('open','active','initiated') and r.product_id=v_product and (r.batch_lot_number is null or r.batch_lot_number=s.batch_no)) order by s.expire_date nulls last,s.id for update loop
   exit when v_remaining=0; v_take:=least(v_remaining,v_stock.product_stock);
   update stocks set product_stock=product_stock-v_take,updated_at=now() where id=v_stock.id;
   insert into stock_movements(business_id,product_id,warehouse_id,stock_id,movement_type,quantity,reference_type,reference_id,batch_no,expire_date,unit_cost,notes,created_by) values(p_business_id,v_product,p_warehouse_id,v_stock.id,'out',v_take,'sale',v_sale_id,v_stock.batch_no,v_stock.expire_date,v_stock.product_stock,'FEFO sale deduction',v_user);
   insert into fefo_logs(business_id,product_id,stock_id,sale_id,batch_no,expire_date,quantity_deducted,quantity_remaining_after,action_type,notes) values(p_business_id,v_product,v_stock.id,v_sale_id,v_stock.batch_no,v_stock.expire_date,v_take,v_stock.product_stock-v_take,'sale_deduction','Atomic FEFO deduction');
   v_remaining:=v_remaining-v_take;
  end loop;
  if v_remaining>0 then raise exception 'FEFO allocation failed for product %',v_product; end if;
  update warehouse_stocks set quantity=quantity-v_qty,updated_at=now() where business_id=p_business_id and warehouse_id=p_warehouse_id and product_id=v_product and quantity>=v_qty;
  if not found then raise exception 'insufficient warehouse stock for product %',v_product; end if;
  insert into sale_details(business_id,sale_id,product_id,price,purchase_price,loss_profit,batch_no,expire_date,quantities) values(p_business_id,v_sale_id,v_product,coalesce((v_item->>'unit_price')::numeric,(select sales_price from products where id=v_product)),0,0,null,null,v_qty);
 end loop;
 insert into audit_logs(business_id,actor_user_id,action,model_type,model_id,description,new_values) values(p_business_id,v_user,'create','sale',v_sale_id,'Sale posted atomically with FEFO and warehouse stock control',jsonb_build_object('invoice_number',p_invoice_number,'total_amount',v_total,'warehouse_id',p_warehouse_id));
 return v_sale_id;
exception when unique_violation then
 select id into v_sale_id from sales where business_id=p_business_id and idempotency_key=p_idempotency_key;
 if v_sale_id is not null then return v_sale_id; end if; raise;
end;
$$;
revoke all on function private.post_sale(bigint,bigint,bigint,text,text,numeric,numeric,numeric,jsonb,text) from public,anon;
grant execute on function private.post_sale(bigint,bigint,bigint,text,text,numeric,numeric,numeric,jsonb,text) to authenticated;
