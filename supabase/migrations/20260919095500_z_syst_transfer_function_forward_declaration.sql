-- Forward declaration for migration ordering.
create schema if not exists private;
-- The warehouse-aware implementation is replaced
-- later after all transfer dependencies exist.
create or replace function private.complete_stock_transfer(p_transfer_id bigint)
returns public.stock_transfers
language plpgsql security definer
set search_path=public,private,pg_temp
as $$
declare v_transfer public.stock_transfers;
begin
  select * into v_transfer from public.stock_transfers where id=p_transfer_id for update;
  if v_transfer.id is null then raise exception 'Stock transfer not found'; end if;
  raise exception 'Stock transfer completion implementation not yet installed';
end;
$$;
revoke all on function private.complete_stock_transfer(bigint) from public,anon,authenticated;
grant execute on function private.complete_stock_transfer(bigint) to authenticated;
