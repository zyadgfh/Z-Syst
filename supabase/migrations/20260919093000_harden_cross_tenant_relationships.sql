create or replace function public.enforce_same_business()
returns trigger language plpgsql as $$
begin
 if new.business_id is null then raise exception 'business_id is required'; end if;
 if tg_table_name='products' then
  if new.category_id is not null and not exists(select 1 from public.categories c where c.id=new.category_id and c.business_id=new.business_id) then raise exception 'category belongs to another business'; end if;
  if new.unit_id is not null and not exists(select 1 from public.units u where u.id=new.unit_id and u.business_id=new.business_id) then raise exception 'unit belongs to another business'; end if;
  if new.tax_id is not null and not exists(select 1 from public.taxes t where t.id=new.tax_id and t.business_id=new.business_id) then raise exception 'tax belongs to another business'; end if;
 end if;
 if tg_table_name='warehouse_stocks' then
  if not exists(select 1 from public.warehouses w where w.id=new.warehouse_id and w.business_id=new.business_id) then raise exception 'warehouse belongs to another business'; end if;
  if not exists(select 1 from public.products p where p.id=new.product_id and p.business_id=new.business_id) then raise exception 'product belongs to another business'; end if;
 end if;
 if tg_table_name='stocks' then
  if not exists(select 1 from public.products p where p.id=new.product_id and p.business_id=new.business_id) then raise exception 'product belongs to another business'; end if;
 end if;
 return new;
end $$;
revoke all on function public.enforce_same_business() from public,anon,authenticated;
create trigger products_same_business before insert or update on public.products for each row execute function public.enforce_same_business();
create trigger warehouse_stocks_same_business before insert or update on public.warehouse_stocks for each row execute function public.enforce_same_business();
create trigger stocks_same_business before insert or update on public.stocks for each row execute function public.enforce_same_business();