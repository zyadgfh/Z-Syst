<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip CHECK constraints for SQLite (not fully supported)
        // These would be applied in PostgreSQL/MySQL production environment
        if (DB::getDriverName() !== 'sqlite') {
            // Add CHECK constraints for data validation
            DB::statement("ALTER TABLE stocks ADD CONSTRAINT check_positive_quantity CHECK (productStock >= 0)");
            DB::statement("ALTER TABLE sales ADD CONSTRAINT check_positive_amount CHECK (totalAmount >= 0 AND paidAmount >= 0)");
            DB::statement("ALTER TABLE purchases ADD CONSTRAINT check_positive_amount CHECK (totalAmount >= 0 AND paidAmount >= 0)");
            DB::statement("ALTER TABLE products ADD CONSTRAINT check_positive_prices CHECK (sales_price >= 0 AND purchase_without_tax >= 0)");
            DB::statement("ALTER TABLE sales ADD CONSTRAINT sales_business_id_check CHECK (business_id IS NOT NULL)");
            DB::statement("ALTER TABLE purchases ADD CONSTRAINT purchases_business_id_check CHECK (business_id IS NOT NULL)");
            DB::statement("ALTER TABLE products ADD CONSTRAINT products_business_id_check CHECK (business_id IS NOT NULL)");
            DB::statement("ALTER TABLE stocks ADD CONSTRAINT stocks_business_id_check CHECK (business_id IS NOT NULL)");
            DB::statement("ALTER TABLE sales ADD CONSTRAINT check_sale_status CHECK (isPaid IN (0, 1))");
            DB::statement("ALTER TABLE purchases ADD CONSTRAINT check_purchase_status CHECK (isPaid IN (0, 1))");
            DB::statement("ALTER TABLE sale_details ADD CONSTRAINT check_positive_quantity CHECK (quantities >= 0)");
            DB::statement("ALTER TABLE purchase_details ADD CONSTRAINT check_positive_quantity CHECK (quantities >= 0)");
            DB::statement("ALTER TABLE sales ADD CONSTRAINT check_discount_amount CHECK (discountAmount >= 0)");
            DB::statement("ALTER TABLE purchases ADD CONSTRAINT check_discount_amount CHECK (discountAmount >= 0)");
        }

        // UNIQUE constraints for tenant-level uniqueness are already defined in:
        // - add_branch_id_to_products_table (unique_product_sku)
        // - add_branch_id_to_stocks_table (unique_stock_batch)
        // - add_branch_id_to_sales_table (unique_sale_invoice)
        // - add_branch_id_to_purchases_table (unique_purchase_invoice)
        // No need to duplicate them here.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove CHECK constraints (skip for SQLite)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE stocks DROP CONSTRAINT check_positive_quantity");
            DB::statement("ALTER TABLE sales DROP CONSTRAINT check_positive_amount");
            DB::statement("ALTER TABLE sales DROP CONSTRAINT check_sale_status");
            DB::statement("ALTER TABLE sales DROP CONSTRAINT check_discount_amount");
            DB::statement("ALTER TABLE sales DROP CONSTRAINT sales_business_id_check");
            DB::statement("ALTER TABLE purchases DROP CONSTRAINT check_positive_amount");
            DB::statement("ALTER TABLE purchases DROP CONSTRAINT check_purchase_status");
            DB::statement("ALTER TABLE purchases DROP CONSTRAINT check_discount_amount");
            DB::statement("ALTER TABLE purchases DROP CONSTRAINT purchases_business_id_check");
            DB::statement("ALTER TABLE products DROP CONSTRAINT check_positive_prices");
            DB::statement("ALTER TABLE products DROP CONSTRAINT products_business_id_check");
            DB::statement("ALTER TABLE stocks DROP CONSTRAINT stocks_business_id_check");
            DB::statement("ALTER TABLE sale_details DROP CONSTRAINT check_positive_quantity");
            DB::statement("ALTER TABLE purchase_details DROP CONSTRAINT check_positive_quantity");
        }

        // UNIQUE constraints are managed by their original migrations
        // (add_branch_id_to_* tables). No drops needed here.
    }
};