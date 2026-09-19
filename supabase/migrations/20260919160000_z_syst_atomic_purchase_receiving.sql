create or replace function private.receive_purchase(p_business_id bigint,p_party_id bigint,p_warehouse_id bigint,p_invoice_number text,p_payment_type text,p_paid_amount numeric,p_discount_amount numeric,p_tax_amount numeric,p_items jsonb,p_idempotency_key text default null)
returns bigint language plpgsql security definer set search_path=public,pg_temp as $$
declare v_id bigint; v_item jsonb; v_product bigint; v_qty int; v_price numeric; v_total numeric:=0; v_stock_id bigint; v_batch text; v_exp date; v_user uuid:=auth.uid();
begin
 if p_business_id is distinct from (select private.current_business_id()) then raise exception 'tenant mismatch'; end if;
 if p_idempotency_key is not null then select id into v_id from purchases where business_id=p_business_id and purchase_data->>'idempotency_key'=p_idempotency_key limit 1; if v_id is not null then return v_id; end if; end if;
 if not exists(select 1 from warehouses where id=p_warehouse_id and business_id=p_business_id and is_active) then raise exception 'warehouse unavailable'; end if;
 for v_item in select * from jsonb_array_elements(p_items) loop
   v_product:=(v_item->>'product_id')::bigint; v_qty:=(v_item->>'quantity')::int; v_price:=coalesce((v_item->>'unit_price')::numeric,0); v_total:=v_total+v_qty*v_price+coalesce((v_item->>'tax_amount')::numeric,0)-coalesce((v_item->>'discount_amount')::numeric,0);
   if v_qty<=0 or not exists(select 1 from products where id=v_product and business_id=p_business_id) then raise exception 'invalid purchase item'; end if;
 end loop;
 v_total:=greatest(v_total-coalesce(p_discount_amount,0)+coalesce(p_tax_amount,0),0);
 insert into purchases(party_id,business_id,user_id,discount_amount,tax_amount,due_amount,paid_amount,total_amount,is_paid,payment_type,invoice_number,purchase_date,purchase_data,note)
 values(p_party_id,p_business_id,v_user,coalesce(p_discount_amount,0),coalesce(p_tax_amount,0),greatest(v_total-coalesce(p_paid_amount,0),0),coalesce(p_paid_amount,0),v_total,coalesce(p_paid_amount,0)>=v_total,p_payment_type,p_invoice_number,now(),jsonb_build_object('idempotency_key',p_idempotency_key,'posted_by_rpc',true),null) returning id into v_id;
 for v_item in select * from jsonb_array_elements(p_items) loop
   v_product:=(v_item->>'product_id')::bigint; v_qty:=(v_item->>'quantity')::int; v_price:=coalesce((v_item->>'unit_price')::numeric,0); v_batch:=nullif(v_item->>'batch_no',''); v_exp:=nullif(v_item->>'expire_date','')::date;
   insert into purchase_items(business_id,purchase_id,product_id,quantity,unit_price,tax_amount,discount_amount,total_amount,batch_no,expire_date) values(p_business_id,v_id,v_product,v_qty,v_price,coalesce((v_item->>'tax_amount')::numeric,0),coalesce((v_item->>'discount_amount')::numeric,0),v_qty*v_price+coalesce((v_item->>'tax_amount')::numeric,0)-coalesce((v_item->>'discount_amount')::numeric,0),v_batch,v_exp);
   insert into purchase_details(business_id,purchase_id,product_id,purchase_without_tax,purchase_with_tax,profit_percent,sales_price,wholesale_price,quantities,batch_no,expire_date) values(p_business_id,v_id,v_product,v_price,v_price+coalesce((v_item->>'tax_amount')::numeric,0),coalesce((v_item->>'profit_percent')::numeric,0),coalesce((v_item->>'sales_price')::numeric,(select sales_price from products where id=v_product)),coalesce((v_item->>'wholesale_price')::numeric,(select wholesale_price from products where id=v_product)),v_qty,v_batch,v_exp);
   select id into v_stock_id from stocks where business_id=p_business_id and product_id=v_product and batch_no is not distinct from v_batch and expire_date is not distinct from v_exp for update limit 1;
   if v_stock_id is null then insert into stocks(business_id,product_id,product_stock,batch_no,expire_date) values(p_business_id,v_product,v_qty,v_batch,v_exp) returning id into v_stock_id; else update stocks set product_stock=product_stock+v_qty,updated_at=now() where id=v_stock_id; end if;
   insert into warehouse_stocks(business_id,warehouse_id,product_id,quantity) values(p_business_id,p_warehouse_id,v_product,v_qty) on conflict (warehouse_id,product_id) do update set quantity=warehouse_stocks.quantity+excluded.quantity,updated_at=now();
   insert into stock_movements(business_id,product_id,warehouse_id,stock_id,movement_type,quantity,reference_type,reference_id,batch_no,expire_date,unit_cost,notes,created_by) values(p_business_id,v_product,p_warehouse_id,v_stock_id,'in',v_qty,'purchase',v_id,v_batch,v_exp,v_price,'Atomic purchase receipt',v_user);
 end loop;
 insert into audit_logs(business_id,actor_user_id,action,model_type,model_id,description,new_values) values(p_business_id,v_user,'create','purchase',v_id,'Purchase received atomically',jsonb_build_object('invoice_number',p_invoice_number,'total_amount',v_total));
 return v_id;
end$$;
revoke all on function private.receive_purchase(bigint,bigint,bigint,text,text,numeric,numeric,numeric,jsonb,text) from public,anon,authenticated; grant execute on function private.receive_purchase(bigint,bigint,bigint,text,text,numeric,numeric,numeric,jsonb,text) to authenticated;