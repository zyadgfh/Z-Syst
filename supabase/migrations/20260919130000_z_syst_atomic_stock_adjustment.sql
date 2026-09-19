create or replace function public.adjust_stock(p_product_id bigint,p_quantity integer,p_direction text,p_warehouse_id bigint default null,p_batch_no text default null,p_expire_date date default null,p_reference_type text default null,p_reference_id bigint default null,p_unit_cost numeric default null,p_notes text default null) returns bigint language plpgsql security definer set search_path=public,pg_temp as $$
declare v_business bigint; v_stock_id bigint; v_new integer; v_qty integer;
begin
 if p_quantity <= 0 then raise exception 'quantity must be positive'; end if;
 if p_direction not in ('in','out') then raise exception 'direction must be in or out'; end if;
 v_business := (select private.current_business_id());
 if v_business is null then raise exception 'business context required'; end if;
 if not exists(select 1 from products where id=p_product_id and business_id=v_business) then raise exception 'product is outside current business'; end if;
 if p_warehouse_id is not null and not exists(select 1 from warehouses where id=p_warehouse_id and business_id=v_business and is_active) then raise exception 'warehouse is outside current business or inactive'; end if;
 if p_direction='in' then
   select id into v_stock_id from stocks where business_id=v_business and product_id=p_product_id and batch_no is not distinct from p_batch_no and expire_date is not distinct from p_expire_date for update;
   if v_stock_id is null then
     insert into stocks(business_id,product_id,product_stock,batch_no,expire_date) values(v_business,p_product_id,p_quantity,p_batch_no,p_expire_date) returning id into v_stock_id;
   else
     update stocks set product_stock=product_stock+p_quantity where id=v_stock_id returning product_stock into v_new;
   end if;
 else
   select id,product_stock into v_stock_id,v_qty from stocks where business_id=v_business and product_id=p_product_id and batch_no is not distinct from p_batch_no and expire_date is not distinct from p_expire_date for update;
   if v_stock_id is null then raise exception 'stock batch not found'; end if;
   if v_qty < p_quantity then raise exception 'insufficient stock'; end if;
   update stocks set product_stock=product_stock-p_quantity where id=v_stock_id returning product_stock into v_new;
 end if;
 if p_warehouse_id is not null then
   insert into warehouse_stocks(business_id,warehouse_id,product_id,quantity) values(v_business,p_warehouse_id,p_product_id,case when p_direction='in' then p_quantity else -p_quantity end)
   on conflict (warehouse_id,product_id) do update set quantity=warehouse_stocks.quantity+excluded.quantity,updated_at=now();
   if exists(select 1 from warehouse_stocks where warehouse_id=p_warehouse_id and product_id=p_product_id and quantity<0) then raise exception 'insufficient warehouse stock'; end if;
 end if;
 insert into stock_movements(business_id,product_id,warehouse_id,stock_id,movement_type,quantity,reference_type,reference_id,batch_no,expire_date,unit_cost,notes,created_by)
 values(v_business,p_product_id,p_warehouse_id,v_stock_id,p_direction,p_quantity,p_reference_type,p_reference_id,p_batch_no,p_expire_date,p_unit_cost,p_notes,(select auth.uid()));
 return v_stock_id;
end $$;
revoke all on function public.adjust_stock(bigint,integer,text,bigint,text,date,text,bigint,numeric,text) from public,anon;
grant execute on function public.adjust_stock(bigint,integer,text,bigint,text,date,text,bigint,numeric,text) to authenticated;