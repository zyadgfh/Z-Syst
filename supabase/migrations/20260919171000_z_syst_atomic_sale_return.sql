create or replace function private.post_sale_return(p_business_id bigint,p_sale_id bigint,p_return_number text,p_items jsonb,p_reason text default null)
returns bigint language plpgsql security definer set search_path=public,pg_temp as $$
declare v_id bigint; v_item jsonb; v_product bigint; v_qty int; v_stock bigint; v_refund numeric; v_user uuid:=auth.uid(); v_total numeric:=0;
begin
 if p_business_id is distinct from (select private.current_business_id()) then raise exception 'tenant mismatch'; end if;
 if not exists(select 1 from sales where id=p_sale_id and business_id=p_business_id) then raise exception 'sale not found'; end if;
 insert into sale_returns(business_id,sale_id,return_number,reason,created_by) values(p_business_id,p_sale_id,p_return_number,p_reason,v_user) returning id into v_id;
 for v_item in select * from jsonb_array_elements(p_items) loop
  v_product:=(v_item->>'product_id')::bigint; v_qty:=(v_item->>'quantity')::int; v_refund:=coalesce((v_item->>'refund_amount')::numeric,0); v_stock:=nullif(v_item->>'stock_id','')::bigint;
  if v_qty<=0 or not exists(select 1 from products where id=v_product and business_id=p_business_id) then raise exception 'invalid return item'; end if;
  if v_stock is not null then update stocks set product_stock=product_stock+v_qty,updated_at=now() where id=v_stock and business_id=p_business_id and product_id=v_product; if not found then raise exception 'invalid return stock'; end if; end if;
  insert into sale_return_items(business_id,sale_return_id,sale_item_id,product_id,stock_id,quantity,refund_amount,batch_no,expire_date) values(p_business_id,v_id,null,v_product,v_stock,v_qty,v_refund,v_item->>'batch_no',nullif(v_item->>'expire_date','')::date);
  v_total:=v_total+v_refund;
  insert into stock_movements(business_id,product_id,warehouse_id,stock_id,movement_type,quantity,reference_type,reference_id,batch_no,expire_date,unit_cost,notes,created_by)
  select p_business_id,v_product,null,v_stock,'in',v_qty,'sale_return',v_id,v_item->>'batch_no',nullif(v_item->>'expire_date','')::date,0,'Atomic sale return',v_user;
 end loop;
 update sale_returns set refund_amount=v_total,updated_at=now() where id=v_id;
 insert into audit_logs(business_id,actor_user_id,action,model_type,model_id,description,new_values) values(p_business_id,v_user,'create','sale_return',v_id,'Sale return posted atomically',jsonb_build_object('refund_amount',v_total));
 return v_id;
end$$;
revoke all on function private.post_sale_return(bigint,bigint,text,jsonb,text) from public,anon,authenticated; grant execute on function private.post_sale_return(bigint,bigint,text,jsonb,text) to authenticated;