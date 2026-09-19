-- Remove duplicate permissive return policies and add FK/query indexes
drop policy if exists purchase_returns_tenant_policy on public.purchase_returns;
drop policy if exists sale_returns_tenant_policy on public.sale_returns;

create index if not exists stock_transfers_business_from_idx
  on public.stock_transfers(business_id,from_warehouse_id,created_at);
create index if not exists stock_transfers_business_to_idx
  on public.stock_transfers(business_id,to_warehouse_id,created_at);
create index if not exists stock_transfers_business_product_idx
  on public.stock_transfers(business_id,product_id,created_at);
create index if not exists warehouse_stocks_business_warehouse_product_idx
  on public.warehouse_stocks(business_id,warehouse_id,product_id);
create index if not exists purchase_returns_business_purchase_idx
  on public.purchase_returns(business_id,purchase_id);
create index if not exists purchase_return_items_business_return_idx
  on public.purchase_return_items(business_id,purchase_return_id);
create index if not exists purchase_return_items_business_product_idx
  on public.purchase_return_items(business_id,product_id);
create index if not exists sale_returns_business_sale_idx
  on public.sale_returns(business_id,sale_id);
create index if not exists sale_return_items_business_return_idx
  on public.sale_return_items(business_id,sale_return_id);
create index if not exists sale_return_items_business_product_idx
  on public.sale_return_items(business_id,product_id);
