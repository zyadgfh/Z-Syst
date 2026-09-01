<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Composite indexes for common query patterns
        
        // Products - business + category + type queries
        Schema::table('products', function (Blueprint $table) {
            $table->index(['business_id', 'category_id', 'type_id'], 'idx_products_business_category_type');
        });
        
        // Sales - business + date + status queries
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['business_id', 'saleDate', 'isPaid'], 'idx_sales_business_date_status');
        });
        
        // Stocks - product + expiry + quantity queries
        Schema::table('stocks', function (Blueprint $table) {
            $table->index(['product_id', 'expire_date', 'productStock'], 'idx_stocks_product_expire_qty');
        });
        
        // Parties - business + type queries
        Schema::table('parties', function (Blueprint $table) {
            $table->index(['business_id', 'party_type'], 'idx_parties_business_type');
        });
        
        // Partial indexes for filtered queries (skip on SQLite - not supported)
        if (DB::getDriverName() !== 'sqlite') {
            // Unpaid sales
            DB::statement("CREATE INDEX idx_sales_unpaid_partial ON sales(id) WHERE isPaid = false");
            
            // Sales with due amounts
            DB::statement("CREATE INDEX idx_sales_with_due_partial ON sales(id) WHERE dueAmount > 0");
            
            // Available stock
            DB::statement("CREATE INDEX idx_stocks_available_partial ON stocks(id) WHERE productStock > 0");
            
            // Stocks with expiry dates
            DB::statement("CREATE INDEX idx_stocks_with_expiry_partial ON stocks(id) WHERE expire_date IS NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop composite indexes
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_business_category_type');
        });
        
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_business_date_status');
        });
        
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropIndex('idx_stocks_product_expire_qty');
        });
        
        Schema::table('parties', function (Blueprint $table) {
            $table->dropIndex('idx_parties_business_type');
        });
        
        // Drop partial indexes (skip on SQLite)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("DROP INDEX IF EXISTS idx_sales_unpaid_partial");
            DB::statement("DROP INDEX IF EXISTS idx_sales_with_due_partial");
            DB::statement("DROP INDEX IF EXISTS idx_stocks_available_partial");
            DB::statement("DROP INDEX IF EXISTS idx_stocks_with_expiry_partial");
        }
    }
};
