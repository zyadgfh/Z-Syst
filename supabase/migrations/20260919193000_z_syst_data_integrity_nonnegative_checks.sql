-- Defense-in-depth data integrity checks.
-- NOT VALID keeps deployment safe for existing legacy rows; constraints can be validated after cleanup.
alter table public.stocks
  add constraint stocks_product_stock_nonnegative_chk check (product_stock >= 0) not valid;
alter table public.warehouse_stocks
  add constraint warehouse_stocks_quantity_nonnegative_chk check (quantity >= 0) not valid;
alter table public.stock_transfers
  add constraint stock_transfers_quantity_positive_chk check (quantity > 0) not valid;
alter table public.cash_registers
  add constraint cash_registers_opening_nonnegative_chk check (opening_balance >= 0) not valid;
alter table public.cash_registers
  add constraint cash_registers_closing_nonnegative_chk check (closing_balance is null or closing_balance >= 0) not valid;
alter table public.cash_register_transactions
  add constraint cash_register_transactions_amount_positive_chk check (amount > 0) not valid;
alter table public.coupons
  add constraint coupons_discount_value_nonnegative_chk check (discount_value >= 0) not valid;
alter table public.coupons
  add constraint coupons_usage_count_nonnegative_chk check (usage_count >= 0) not valid;
alter table public.coupon_redemptions
  add constraint coupon_redemptions_discount_nonnegative_chk check (discount_amount >= 0) not valid;
