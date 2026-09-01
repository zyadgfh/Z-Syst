<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Check if index exists
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = config('database.default');
        
        if ($connection === 'sqlite') {
            $result = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND name=?", [$index]);
        } else {
            $result = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
        }
        
        return count($result) > 0;
    }

    /**
     * Safe add index
     */
    private function safeAddIndex(string $table, $columns, string $indexName): void
    {
        // Ensure columns is always an array
        $columns = is_array($columns) ? $columns : [$columns];
        
        if (!$this->indexExists($table, $indexName)) {
            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        }
    }

    /**
     * Safe drop index
     */
    private function safeDropIndex(string $table, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }

    public function up(): void
    {
        // Users table
        $this->safeAddIndex('users', 'business_id', 'idx_users_business_id');
        $this->safeAddIndex('users', 'role', 'idx_users_role');
        $this->safeAddIndex('users', ['business_id', 'role'], 'idx_users_business_role');

        // Parties table
        $this->safeAddIndex('parties', 'business_id', 'idx_parties_business_id');
        $this->safeAddIndex('parties', 'type', 'idx_parties_type');
        $this->safeAddIndex('parties', 'phone', 'idx_parties_phone');
        $this->safeAddIndex('parties', ['business_id', 'name'], 'idx_parties_business_name');

        // Purchase table
        $this->safeAddIndex('purchases', 'business_id', 'idx_purchases_business_id');
        $this->safeAddIndex('purchases', 'party_id', 'idx_purchases_party_id');
        $this->safeAddIndex('purchases', 'purchaseDate', 'idx_purchases_date');
        $this->safeAddIndex('purchases', ['business_id', 'purchaseDate'], 'idx_purchases_business_date');

        // Purchase details
        $this->safeAddIndex('purchase_details', 'purchase_id', 'idx_purchase_details_purchase_id');
        $this->safeAddIndex('purchase_details', 'product_id', 'idx_purchase_details_product_id');

        // Sale details
        $this->safeAddIndex('sale_details', 'sale_id', 'idx_sale_details_sale_id');
        $this->safeAddIndex('sale_details', 'product_id', 'idx_sale_details_product_id');
        $this->safeAddIndex('sale_details', ['sale_id', 'product_id'], 'idx_sale_details_sale_product');

        // Stock table
        $this->safeAddIndex('stocks', 'business_id', 'idx_stocks_business_id');
        $this->safeAddIndex('stocks', 'product_id', 'idx_stocks_product_id');
        $this->safeAddIndex('stocks', ['business_id', 'product_id'], 'idx_stocks_business_product');
        $this->safeAddIndex('stocks', 'warehouse_id', 'idx_stocks_warehouse_id');

        // Sales table
        $this->safeAddIndex('sales', 'business_id', 'idx_sales_business_id');
        $this->safeAddIndex('sales', 'party_id', 'idx_sales_party_id');
        $this->safeAddIndex('sales', 'user_id', 'idx_sales_user_id');

        // Plan subscribes
        $this->safeAddIndex('plan_subscribes', 'business_id', 'idx_plan_subscribes_business_id');
        $this->safeAddIndex('plan_subscribes', 'plan_id', 'idx_plan_subscribes_plan_id');
        $this->safeAddIndex('plan_subscribes', 'payment_status', 'idx_plan_subscribes_payment_status');

        // Expenses
        $this->safeAddIndex('expenses', 'business_id', 'idx_expenses_business_id');
        $this->safeAddIndex('expenses', 'category_id', 'idx_expenses_category_id');
        $this->safeAddIndex('expenses', 'date', 'idx_expenses_date');

        // Incomes
        $this->safeAddIndex('incomes', 'business_id', 'idx_incomes_business_id');
        $this->safeAddIndex('incomes', 'category_id', 'idx_incomes_category_id');
        $this->safeAddIndex('incomes', 'date', 'idx_incomes_date');

        // Sale returns
        $this->safeAddIndex('sale_returns', 'business_id', 'idx_sale_returns_business_id');
        $this->safeAddIndex('sale_returns', 'sale_id', 'idx_sale_returns_sale_id');
        $this->safeAddIndex('sale_returns', 'party_id', 'idx_sale_returns_party_id');

        // Purchase returns
        $this->safeAddIndex('purchase_returns', 'business_id', 'idx_purchase_returns_business_id');
        $this->safeAddIndex('purchase_returns', 'purchase_id', 'idx_purchase_returns_purchase_id');
        $this->safeAddIndex('purchase_returns', 'party_id', 'idx_purchase_returns_party_id');

        // Due collects
        $this->safeAddIndex('due_collects', 'business_id', 'idx_due_collects_business_id');
        $this->safeAddIndex('due_collects', 'sale_id', 'idx_due_collects_sale_id');
        $this->safeAddIndex('due_collects', 'party_id', 'idx_due_collects_party_id');

        // Notifications
        $this->safeAddIndex('notifications', 'user_id', 'idx_notifications_user_id');

        // Categories
        $this->safeAddIndex('categories', 'business_id', 'idx_categories_business_id');

        // Products
        $this->safeAddIndex('products', 'business_id', 'idx_products_business_id');
    }

    public function down(): void
    {
        $this->safeDropIndex('users', 'idx_users_business_id');
        $this->safeDropIndex('users', 'idx_users_role');
        $this->safeDropIndex('users', 'idx_users_business_role');

        $this->safeDropIndex('parties', 'idx_parties_business_id');
        $this->safeDropIndex('parties', 'idx_parties_type');
        $this->safeDropIndex('parties', 'idx_parties_phone');
        $this->safeDropIndex('parties', 'idx_parties_business_name');

        $this->safeDropIndex('purchases', 'idx_purchases_business_id');
        $this->safeDropIndex('purchases', 'idx_purchases_party_id');
        $this->safeDropIndex('purchases', 'idx_purchases_date');
        $this->safeDropIndex('purchases', 'idx_purchases_business_date');

        $this->safeDropIndex('purchase_details', 'idx_purchase_details_purchase_id');
        $this->safeDropIndex('purchase_details', 'idx_purchase_details_product_id');

        $this->safeDropIndex('sale_details', 'idx_sale_details_sale_id');
        $this->safeDropIndex('sale_details', 'idx_sale_details_product_id');
        $this->safeDropIndex('sale_details', 'idx_sale_details_sale_product');

        $this->safeDropIndex('stocks', 'idx_stocks_business_id');
        $this->safeDropIndex('stocks', 'idx_stocks_product_id');
        $this->safeDropIndex('stocks', 'idx_stocks_business_product');
        $this->safeDropIndex('stocks', 'idx_stocks_warehouse_id');

        $this->safeDropIndex('sales', 'idx_sales_business_id');
        $this->safeDropIndex('sales', 'idx_sales_party_id');
        $this->safeDropIndex('sales', 'idx_sales_user_id');

        $this->safeDropIndex('plan_subscribes', 'idx_plan_subscribes_business_id');
        $this->safeDropIndex('plan_subscribes', 'idx_plan_subscribes_plan_id');
        $this->safeDropIndex('plan_subscribes', 'idx_plan_subscribes_payment_status');

        $this->safeDropIndex('expenses', 'idx_expenses_business_id');
        $this->safeDropIndex('expenses', 'idx_expenses_category_id');
        $this->safeDropIndex('expenses', 'idx_expenses_date');

        $this->safeDropIndex('incomes', 'idx_incomes_business_id');
        $this->safeDropIndex('incomes', 'idx_incomes_category_id');
        $this->safeDropIndex('incomes', 'idx_incomes_date');

        $this->safeDropIndex('sale_returns', 'idx_sale_returns_business_id');
        $this->safeDropIndex('sale_returns', 'idx_sale_returns_sale_id');
        $this->safeDropIndex('sale_returns', 'idx_sale_returns_party_id');

        $this->safeDropIndex('purchase_returns', 'idx_purchase_returns_business_id');
        $this->safeDropIndex('purchase_returns', 'idx_purchase_returns_purchase_id');
        $this->safeDropIndex('purchase_returns', 'idx_purchase_returns_party_id');

        $this->safeDropIndex('due_collects', 'idx_due_collects_business_id');
        $this->safeDropIndex('due_collects', 'idx_due_collects_sale_id');
        $this->safeDropIndex('due_collects', 'idx_due_collects_party_id');

        $this->safeDropIndex('notifications', 'idx_notifications_user_id');

        $this->safeDropIndex('categories', 'idx_categories_business_id');

        $this->safeDropIndex('products', 'idx_products_business_id');
    }
};
