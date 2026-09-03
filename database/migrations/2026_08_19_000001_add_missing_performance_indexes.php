<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasIndex('users', 'idx_users_business_id')) {
                $table->index('business_id', 'idx_users_business_id');
            }
            if (!Schema::hasIndex('users', 'idx_users_role')) {
                $table->index('role', 'idx_users_role');
            }
            if (!Schema::hasIndex('users', 'idx_users_business_role')) {
                $table->index(['business_id', 'role'], 'idx_users_business_role');
            }
        });

        // Parties table
        Schema::table('parties', function (Blueprint $table) {
            if (!Schema::hasIndex('parties', 'idx_parties_business_id')) {
                $table->index('business_id', 'idx_parties_business_id');
            }
            if (!Schema::hasIndex('parties', 'idx_parties_type')) {
                $table->index('type', 'idx_parties_type');
            }
            if (!Schema::hasIndex('parties', 'idx_parties_phone')) {
                $table->index('phone', 'idx_parties_phone');
            }
            if (!Schema::hasIndex('parties', 'idx_parties_business_name')) {
                $table->index(['business_id', 'name'], 'idx_parties_business_name');
            }
        });

        // Purchase table
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasIndex('purchases', 'idx_purchases_business_id')) {
                $table->index('business_id', 'idx_purchases_business_id');
            }
            if (!Schema::hasIndex('purchases', 'idx_purchases_party_id')) {
                $table->index('party_id', 'idx_purchases_party_id');
            }
            if (!Schema::hasIndex('purchases', 'idx_purchases_date')) {
                $table->index('purchaseDate', 'idx_purchases_date');
            }
            if (!Schema::hasIndex('purchases', 'idx_purchases_business_date')) {
                $table->index(['business_id', 'purchaseDate'], 'idx_purchases_business_date');
            }
        });

        // Purchase details
        Schema::table('purchase_details', function (Blueprint $table) {
            if (!Schema::hasIndex('purchase_details', 'idx_purchase_details_purchase_id')) {
                $table->index('purchase_id', 'idx_purchase_details_purchase_id');
            }
            if (!Schema::hasIndex('purchase_details', 'idx_purchase_details_product_id')) {
                $table->index('product_id', 'idx_purchase_details_product_id');
            }
        });

        // Sale details
        Schema::table('sale_details', function (Blueprint $table) {
            if (!Schema::hasIndex('sale_details', 'idx_sale_details_sale_id')) {
                $table->index('sale_id', 'idx_sale_details_sale_id');
            }
            if (!Schema::hasIndex('sale_details', 'idx_sale_details_product_id')) {
                $table->index('product_id', 'idx_sale_details_product_id');
            }
            if (!Schema::hasIndex('sale_details', 'idx_sale_details_sale_product')) {
                $table->index(['sale_id', 'product_id'], 'idx_sale_details_sale_product');
            }
        });

        // Stock table
        Schema::table('stocks', function (Blueprint $table) {
            if (!Schema::hasIndex('stocks', 'idx_stocks_business_id')) {
                $table->index('business_id', 'idx_stocks_business_id');
            }
            if (!Schema::hasIndex('stocks', 'idx_stocks_product_id')) {
                $table->index('product_id', 'idx_stocks_product_id');
            }
            if (!Schema::hasIndex('stocks', 'idx_stocks_business_product')) {
                $table->index(['business_id', 'product_id'], 'idx_stocks_business_product');
            }
            if (!Schema::hasIndex('stocks', 'idx_stocks_warehouse_id')) {
                $table->index('warehouse_id', 'idx_stocks_warehouse_id');
            }
        });

        // Sales table
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasIndex('sales', 'idx_sales_business_id')) {
                $table->index('business_id', 'idx_sales_business_id');
            }
            if (!Schema::hasIndex('sales', 'idx_sales_party_id')) {
                $table->index('party_id', 'idx_sales_party_id');
            }
            if (!Schema::hasIndex('sales', 'idx_sales_user_id')) {
                $table->index('user_id', 'idx_sales_user_id');
            }
        });

        // Plan subscribes
        Schema::table('plan_subscribes', function (Blueprint $table) {
            if (!Schema::hasIndex('plan_subscribes', 'idx_plan_subscribes_business_id')) {
                $table->index('business_id', 'idx_plan_subscribes_business_id');
            }
            if (!Schema::hasIndex('plan_subscribes', 'idx_plan_subscribes_plan_id')) {
                $table->index('plan_id', 'idx_plan_subscribes_plan_id');
            }
            if (!Schema::hasIndex('plan_subscribes', 'idx_plan_subscribes_payment_status')) {
                $table->index('payment_status', 'idx_plan_subscribes_payment_status');
            }
        });

        // Expenses
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasIndex('expenses', 'idx_expenses_business_id')) {
                $table->index('business_id', 'idx_expenses_business_id');
            }
            if (!Schema::hasIndex('expenses', 'idx_expenses_category_id')) {
                $table->index('category_id', 'idx_expenses_category_id');
            }
            if (!Schema::hasIndex('expenses', 'idx_expenses_date')) {
                $table->index('date', 'idx_expenses_date');
            }
        });

        // Incomes
        Schema::table('incomes', function (Blueprint $table) {
            if (!Schema::hasIndex('incomes', 'idx_incomes_business_id')) {
                $table->index('business_id', 'idx_incomes_business_id');
            }
            if (!Schema::hasIndex('incomes', 'idx_incomes_category_id')) {
                $table->index('category_id', 'idx_incomes_category_id');
            }
            if (!Schema::hasIndex('incomes', 'idx_incomes_date')) {
                $table->index('date', 'idx_incomes_date');
            }
        });

        // Sale returns
        Schema::table('sale_returns', function (Blueprint $table) {
            if (!Schema::hasIndex('sale_returns', 'idx_sale_returns_business_id')) {
                $table->index('business_id', 'idx_sale_returns_business_id');
            }
            if (!Schema::hasIndex('sale_returns', 'idx_sale_returns_sale_id')) {
                $table->index('sale_id', 'idx_sale_returns_sale_id');
            }
            if (!Schema::hasIndex('sale_returns', 'idx_sale_returns_party_id')) {
                $table->index('party_id', 'idx_sale_returns_party_id');
            }
        });

        // Purchase returns
        Schema::table('purchase_returns', function (Blueprint $table) {
            if (!Schema::hasIndex('purchase_returns', 'idx_purchase_returns_business_id')) {
                $table->index('business_id', 'idx_purchase_returns_business_id');
            }
            if (!Schema::hasIndex('purchase_returns', 'idx_purchase_returns_purchase_id')) {
                $table->index('purchase_id', 'idx_purchase_returns_purchase_id');
            }
            if (!Schema::hasIndex('purchase_returns', 'idx_purchase_returns_party_id')) {
                $table->index('party_id', 'idx_purchase_returns_party_id');
            }
        });

        // Due collects
        Schema::table('due_collects', function (Blueprint $table) {
            if (!Schema::hasIndex('due_collects', 'idx_due_collects_business_id')) {
                $table->index('business_id', 'idx_due_collects_business_id');
            }
            if (!Schema::hasIndex('due_collects', 'idx_due_collects_sale_id')) {
                $table->index('sale_id', 'idx_due_collects_sale_id');
            }
            if (!Schema::hasIndex('due_collects', 'idx_due_collects_party_id')) {
                $table->index('party_id', 'idx_due_collects_party_id');
            }
        });

        // Notifications
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasIndex('notifications', 'idx_notifications_user_id')) {
                $table->index('user_id', 'idx_notifications_user_id');
            }
            if (!Schema::hasIndex('notifications', 'idx_notifications_user_read')) {
                $table->index(['user_id', 'read_at'], 'idx_notifications_user_read');
            }
        });

        // Categories
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasIndex('categories', 'idx_categories_business_id')) {
                $table->index('business_id', 'idx_categories_business_id');
            }
        });

        // Products
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasIndex('products', 'idx_products_business_id')) {
                $table->index('business_id', 'idx_products_business_id');
            }
        });

        // Composite dashboard indexes (skip on SQLite - may conflict)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("CREATE INDEX idx_sales_dashboard ON sales(business_id, saleDate, totalAmount)");
            DB::statement("CREATE INDEX idx_purchases_dashboard ON purchases(business_id, purchaseDate, totalAmount)");
            DB::statement("CREATE INDEX idx_expenses_dashboard ON expenses(business_id, date, amount)");
            DB::statement("CREATE INDEX idx_incomes_dashboard ON incomes(business_id, date, amount)");
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_business_id');
            $table->dropIndex('idx_users_role');
            $table->dropIndex('idx_users_business_role');
        });

        Schema::table('parties', function (Blueprint $table) {
            $table->dropIndex('idx_parties_business_id');
            $table->dropIndex('idx_parties_type');
            $table->dropIndex('idx_parties_phone');
            $table->dropIndex('idx_parties_business_name');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex('idx_purchases_business_id');
            $table->dropIndex('idx_purchases_party_id');
            $table->dropIndex('idx_purchases_date');
            $table->dropIndex('idx_purchases_business_date');
        });

        Schema::table('purchase_details', function (Blueprint $table) {
            $table->dropIndex('idx_purchase_details_purchase_id');
            $table->dropIndex('idx_purchase_details_product_id');
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropIndex('idx_sale_details_sale_id');
            $table->dropIndex('idx_sale_details_product_id');
            $table->dropIndex('idx_sale_details_sale_product');
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropIndex('idx_stocks_business_id');
            $table->dropIndex('idx_stocks_product_id');
            $table->dropIndex('idx_stocks_business_product');
            $table->dropIndex('idx_stocks_warehouse_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_business_id');
            $table->dropIndex('idx_sales_party_id');
            $table->dropIndex('idx_sales_user_id');
        });

        Schema::table('plan_subscribes', function (Blueprint $table) {
            $table->dropIndex('idx_plan_subscribes_business_id');
            $table->dropIndex('idx_plan_subscribes_plan_id');
            $table->dropIndex('idx_plan_subscribes_payment_status');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('idx_expenses_business_id');
            $table->dropIndex('idx_expenses_category_id');
            $table->dropIndex('idx_expenses_date');
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->dropIndex('idx_incomes_business_id');
            $table->dropIndex('idx_incomes_category_id');
            $table->dropIndex('idx_incomes_date');
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropIndex('idx_sale_returns_business_id');
            $table->dropIndex('idx_sale_returns_sale_id');
            $table->dropIndex('idx_sale_returns_party_id');
        });

        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropIndex('idx_purchase_returns_business_id');
            $table->dropIndex('idx_purchase_returns_purchase_id');
            $table->dropIndex('idx_purchase_returns_party_id');
        });

        Schema::table('due_collects', function (Blueprint $table) {
            $table->dropIndex('idx_due_collects_business_id');
            $table->dropIndex('idx_due_collects_sale_id');
            $table->dropIndex('idx_due_collects_party_id');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_user_id');
            $table->dropIndex('idx_notifications_user_read');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('idx_categories_business_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_business_id');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("DROP INDEX IF EXISTS idx_sales_dashboard");
            DB::statement("DROP INDEX IF EXISTS idx_purchases_dashboard");
            DB::statement("DROP INDEX IF EXISTS idx_expenses_dashboard");
            DB::statement("DROP INDEX IF EXISTS idx_incomes_dashboard");
        }
    }
};
