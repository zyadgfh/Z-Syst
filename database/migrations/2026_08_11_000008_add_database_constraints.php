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

        // Add UNIQUE constraints for tenant-level uniqueness (supported in SQLite)
        Schema::table('products', function (Blueprint $table) {
            // Ensure unique SKU per business/branch
            $table->unique(['business_id', 'branch_id', 'productCode'], 'unique_product_sku_tenant');
        });

        Schema::table('stocks', function (Blueprint $table) {
            // Ensure unique batch per business/branch/product
            $table->unique(['business_id', 'branch_id', 'product_id', 'batch_no'], 'unique_stock_batch_tenant');
        });

        Schema::table('sales', function (Blueprint $table) {
            // Ensure unique invoice number per business/branch
            $table->unique(['business_id', 'branch_id', 'invoiceNumber'], 'unique_sale_invoice_tenant');
        });

        Schema::table('purchases', function (Blueprint $table) {
            // Ensure unique invoice number per business/branch
            $table->unique(['business_id', 'branch_id', 'invoiceNumber'], 'unique_purchase_invoice_tenant');
        });
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

        // Remove UNIQUE constraints
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('unique_product_sku_tenant');
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropUnique('unique_stock_batch_tenant');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('unique_sale_invoice_tenant');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique('unique_purchase_invoice_tenant');
        });
    }
};