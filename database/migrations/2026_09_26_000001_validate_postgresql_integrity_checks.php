<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $constraints = [
            'warehouse_stocks' => ['warehouse_stocks_quantity_nonnegative_chk'],
            'stocks' => ['stocks_product_stock_nonnegative_chk'],
            'stock_transfers' => ['stock_transfers_quantity_positive_chk'],
            'coupons' => [
                'coupons_discount_value_nonnegative_chk',
                'coupons_usage_count_nonnegative_chk',
            ],
            'cash_registers' => [
                'cash_registers_closing_nonnegative_chk',
                'cash_registers_opening_nonnegative_chk',
            ],
            'cash_register_transactions' => ['cash_register_transactions_amount_positive_chk'],
            'coupon_redemptions' => ['coupon_redemptions_discount_nonnegative_chk'],
        ];

        foreach ($constraints as $table => $names) {
            foreach ($names as $name) {
                DB::statement(sprintf(
                    'ALTER TABLE "%s" VALIDATE CONSTRAINT "%s"',
                    $table,
                    $name
                ));
            }
        }
    }

    public function down(): void
    {
        // Validation state cannot be reverted safely without replacing
        // the constraint with a NOT VALID definition.
    }
};
