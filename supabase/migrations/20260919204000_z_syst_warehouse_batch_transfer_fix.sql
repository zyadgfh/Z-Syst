-- Z-Syst warehouse-aware stock transfer hardening.
-- Final implementation replacing the early migration-order forward declaration.

create or replace function private.complete_stock_transfer(p_transfer_id bigint)
returns public.stock_transfers
language plpgsql security definer
set search_path=public,private,pg_temp
as $$
declare
 v_business_id bigint:=private.current_business_id();v_user_id uuid:=auth.uid();v_transfer public.stock_transfers;
 v_from_qty integer;v_stock record;v_map record;v_remaining integer;v_take integer;v_mapped integer;
begin
 if v_business_id is null then raise exception 'No active business context';end if;
 select * into v_transfer from public.stock_transfers where id=p_transfer_id and business_id=v_business_id for update;
 if v_transfer.id is null then raise exception 'Stock transfer not found';end if;
 if v_transfer.status<>'pending' then return v_transfer;end if;
 if v_transfer.from_warehouse_id=v_transfer.to_warehouse_id then raise exception 'Source and destination warehouses must differ';end if;
 if v_transfer.quantity<=0 then raise exception 'Transfer quantity must be positive';end if;
 if not exists(select 1 from warehouses where id=v_transfer.from_warehouse_id and business_id=v_business_id and is_active) then raise exception 'Source warehouse unavailable';end if;
 if not exists(select 1 from warehouses where id=v_transfer.to_warehouse_id and business_id=v_business_id and is_active) then raise exception 'Destination warehouse unavailable';end if;
 if not exists(select 1 from products where id=v_transfer.product_id and business_id=v_business_id) then raise exception 'Product not found in tenant';end if;
 select ws.quantity into v_from_qty from public.warehouse_stocks ws
 where ws.business_id=v_business_id and ws.warehouse_id=v_transfer.from_warehouse_id and ws.product_id=v_transfer.product_id for update;
 if coalesce(v_from_qty,0)<v_transfer.quantity then raise exception 'Insufficient source warehouse stock';end if;
 select coalesce(sum(quantity),0) into v_mapped from warehouse_stock_batches
 where business_id=v_business_id and warehouse_id=v_transfer.from_warehouse_id and product_id=v_transfer.product_id;
 update public.warehouse_stocks set quantity=quantity-v_transfer.quantity,updated_at=now()
 where business_id=v_business_id and warehouse_id=v_transfer.from_warehouse_id and product_id=v_transfer.product_id;
 insert into public.warehouse_stocks(business_id,warehouse_id,product_id,quantity)
 values(v_business_id,v_transfer.to_warehouse_id,v_transfer.product_id,v_transfer.quantity)
 on conflict (warehouse_id,product_id) do update set quantity=public.warehouse_stocks.quantity+excluded.quantity,updated_at=now();
 v_remaining:=v_transfer.quantity;
 if v_mapped>=v_transfer.quantity then
  for v_map in
   select wsb.*,s.batch_no,s.expire_date from warehouse_stock_batches wsb
   join stocks s on s.id=wsb.stock_id and s.business_id=wsb.business_id
   where wsb.business_id=v_business_id and wsb.warehouse_id=v_transfer.from_warehouse_id and wsb.product_id=v_transfer.product_id and wsb.quantity>0
   order by s.expire_date nulls last,s.id for update of wsb
  loop
   exit when v_remaining=0;v_take:=least(v_remaining,v_map.quantity);
   update warehouse_stock_batches set quantity=quantity-v_take,updated_at=now() where id=v_map.id;
   insert into warehouse_stock_batches(business_id,warehouse_id,product_id,stock_id,quantity)
   values(v_business_id,v_transfer.to_warehouse_id,v_transfer.product_id,v_map.stock_id,v_take)
   on conflict (warehouse_id,product_id,stock_id) do update set quantity=warehouse_stock_batches.quantity+excluded.quantity,updated_at=now();
   insert into stock_movements(business_id,product_id,warehouse_id,stock_id,movement_type,quantity,reference_type,reference_id,batch_no,expire_date,unit_cost,notes,created_by)
   values(v_business_id,v_transfer.product_id,v_transfer.from_warehouse_id,v_map.stock_id,'transfer_out',-v_take,'stock_transfer',v_transfer.id,v_map.batch_no,v_map.expire_date,0,v_transfer.notes,v_user_id),
         (v_business_id,v_transfer.product_id,v_transfer.to_warehouse_id,v_map.stock_id,'transfer_in',v_take,'stock_transfer',v_transfer.id,v_map.batch_no,v_map.expire_date,0,v_transfer.notes,v_user_id);
   insert into traceability_logs(business_id,product_id,batch_lot_number,from_warehouse_id,to_warehouse_id,type,quantity,notes)
   values(v_business_id,v_transfer.product_id,v_map.batch_no,v_transfer.from_warehouse_id,v_transfer.to_warehouse_id,'stock_transfer',v_take,coalesce(v_transfer.notes,'Warehouse-aware batch transfer'));
   v_remaining:=v_remaining-v_take;
  end loop;
 else
  select * into v_stock from stocks where business_id=v_business_id and product_id=v_transfer.product_id order by expire_date nulls last,id limit 1 for update;
  if v_stock.id is not null then
   insert into stock_movements(business_id,product_id,warehouse_id,stock_id,movement_type,quantity,reference_type,reference_id,batch_no,expire_date,unit_cost,notes,created_by)
   values(v_business_id,v_transfer.product_id,v_transfer.from_warehouse_id,v_stock.id,'transfer_out',-v_transfer.quantity,'stock_transfer',v_transfer.id,v_stock.batch_no,v_stock.expire_date,0,'Legacy transfer; warehouse-batch mapping incomplete',v_user_id),
         (v_business_id,v_transfer.product_id,v_transfer.to_warehouse_id,v_stock.id,'transfer_in',v_transfer.quantity,'stock_transfer',v_transfer.id,v_stock.batch_no,v_stock.expire_date,0,'Legacy transfer; warehouse-batch mapping incomplete',v_user_id);
   insert into traceability_logs(business_id,product_id,batch_lot_number,from_warehouse_id,to_warehouse_id,type,quantity,notes)
   values(v_business_id,v_transfer.product_id,v_stock.batch_no,v_transfer.from_warehouse_id,v_transfer.to_warehouse_id,'stock_transfer',v_transfer.quantity,coalesce(v_transfer.notes,'Legacy warehouse transfer'));
  end if;
  v_remaining:=0;
 end if;
 if v_remaining>0 then raise exception 'Warehouse batch allocation failed for stock transfer';end if;
 update public.stock_transfers set status='completed',completed_at=now(),updated_at=now()
 where id=v_transfer.id and business_id=v_business_id returning * into v_transfer;
 insert into public.audit_logs(business_id,actor_user_id,action,model_type,model_id,description,new_values)
 values(v_business_id,v_user_id,'stock_transfer.completed','stock_transfer',v_transfer.id,'Atomic warehouse-aware stock transfer completed',
        jsonb_build_object('from_warehouse_id',v_transfer.from_warehouse_id,'to_warehouse_id',v_transfer.to_warehouse_id,'product_id',v_transfer.product_id,'quantity',v_transfer.quantity,'batch_aware',v_mapped>=v_transfer.quantity));
 return v_transfer;
end;
$$;
revoke all on function private.complete_stock_transfer(bigint) from public,anon,authenticated;
grant execute on function private.complete_stock_transfer(bigint) to authenticated;
