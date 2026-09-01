<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Products table indexes
        Schema::table('products', function (Blueprint $table) {
            $table->index('productName', 'idx_products_name');
            $table->index('productCode', 'idx_products_code');
            $table->index(['business_id', 'category_id'], 'idx_products_business_category');
        });

        // Stocks table indexes (FEFO critical)
        Schema::table('stocks', function (Blueprint $table) {
            $table->index('expire_date', 'idx_stocks_expire_date');
            $table->index('batch_no', 'idx_stocks_batch');
            $table->index(['product_id', 'expire_date'], 'idx_stocks_product_expire');
        });

        // Sales table indexes
        Schema::table('sales', function (Blueprint $table) {
            $table->index('saleDate', 'idx_sales_date');
            $table->index('invoiceNumber', 'idx_sales_invoice');
            $table->index(['business_id', 'saleDate'], 'idx_sales_business_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Products table indexes
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_name');
            $table->dropIndex('idx_products_code');
            $table->dropIndex('idx_products_business_category');
        });

        // Stocks table indexes
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropIndex('idx_stocks_expire_date');
            $table->dropIndex('idx_stocks_batch');
            $table->dropIndex('idx_stocks_product_expire');
        });

        // Sales table indexes
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_date');
            $table->dropIndex('idx_sales_invoice');
            $table->dropIndex('idx_sales_business_date');
        });
    }
};
