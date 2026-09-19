create or replace function public.enforce_child_same_business() returns trigger language plpgsql as $$
begin
 if tg_table_name='purchase_details' and not exists(select 1 from public.purchases x where x.id=new.purchase_id and x.business_id=new.business_id) then raise exception 'purchase belongs to another business'; end if;
 if tg_table_name='sale_details' and not exists(select 1 from public.sales x where x.id=new.sale_id and x.business_id=new.business_id) then raise exception 'sale belongs to another business'; end if;
 if tg_table_name='purchase_return_details' and not exists(select 1 from public.purchase_returns x where x.id=new.purchase_return_id and x.business_id=new.business_id) then raise exception 'purchase return belongs to another business'; end if;
 if tg_table_name='sale_return_details' and not exists(select 1 from public.sale_returns x where x.id=new.sale_return_id and x.business_id=new.business_id) then raise exception 'sale return belongs to another business'; end if;
 if tg_table_name='purchase_return_details' and not exists(select 1 from public.purchase_details x where x.id=new.purchase_detail_id and x.business_id=new.business_id) then raise exception 'purchase detail belongs to another business'; end if;
 if tg_table_name='sale_return_details' and not exists(select 1 from public.sale_details x where x.id=new.sale_detail_id and x.business_id=new.business_id) then raise exception 'sale detail belongs to another business'; end if;
 return new;
end $$;
revoke all on function public.enforce_child_same_business() from public,anon,authenticated;
create trigger purchase_details_same_business before insert or update on public.purchase_details for each row execute function public.enforce_child_same_business();
create trigger sale_details_same_business before insert or update on public.sale_details for each row execute function public.enforce_child_same_business();
create trigger purchase_return_details_same_business before insert or update on public.purchase_return_details for each row execute function public.enforce_child_same_business();
create trigger sale_return_details_same_business before insert or update on public.sale_return_details for each row execute function public.enforce_child_same_business();