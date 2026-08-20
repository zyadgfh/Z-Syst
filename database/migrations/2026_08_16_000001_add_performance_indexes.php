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
        // Products Table Indexes
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasIndex('products', 'idx_products_business_status')) {
                $table->index(['business_id', 'status'], 'idx_products_business_status');
            }
            if (!Schema::hasIndex('products', 'idx_products_business_category')) {
                $table->index(['business_id', 'category_id'], 'idx_products_business_category');
            }
            if (!Schema::hasIndex('products', 'idx_products_code')) {
                $table->index('productCode', 'idx_products_code');
            }
            if (!Schema::hasIndex('products', 'idx_products_name')) {
                $table->index('productName', 'idx_products_name');
            }
        });

        // Sales Table Indexes
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasIndex('sales', 'idx_sales_business_date')) {
                $table->index(['business_id', 'saleDate'], 'idx_sales_business_date');
            }
            if (!Schema::hasIndex('sales', 'idx_sales_invoice')) {
                $table->index('invoiceNumber', 'idx_sales_invoice');
            }
            if (!Schema::hasIndex('sales', 'idx_sales_business_paid')) {
                $table->index(['business_id', 'isPaid'], 'idx_sales_business_paid');
            }
        });

        // Stocks Table Indexes
        Schema::table('stocks', function (Blueprint $table) {
            if (!Schema::hasIndex('stocks', 'idx_stocks_product_expire')) {
                $table->index(['product_id', 'expire_date'], 'idx_stocks_product_expire');
            }
            if (!Schema::hasIndex('stocks', 'idx_stocks_batch')) {
                $table->index('batch_no', 'idx_stocks_batch');
            }
            if (!Schema::hasIndex('stocks', 'idx_stocks_business_product')) {
                $table->index(['business_id', 'product_id'], 'idx_stocks_business_product');
            }
        });

        // Sale Details Indexes
        Schema::table('sale_details', function (Blueprint $table) {
            if (!Schema::hasIndex('sale_details', 'idx_sale_details_sale')) {
                $table->index('sale_id', 'idx_sale_details_sale');
            }
            if (!Schema::hasIndex('sale_details', 'idx_sale_details_product')) {
                $table->index('product_id', 'idx_sale_details_product');
            }
        });

        // Purchase Details Indexes
        Schema::table('purchase_details', function (Blueprint $table) {
            if (!Schema::hasIndex('purchase_details', 'idx_purchase_details_purchase')) {
                $table->index('purchase_id', 'idx_purchase_details_purchase');
            }
            if (!Schema::hasIndex('purchase_details', 'idx_purchase_details_product')) {
                $table->index('product_id', 'idx_purchase_details_product');
            }
        });

        // Parties Table Indexes
        Schema::table('parties', function (Blueprint $table) {
            if (!Schema::hasIndex('parties', 'idx_parties_business_type')) {
                $table->index(['business_id', 'partyType'], 'idx_parties_business_type');
            }
            if (!Schema::hasIndex('parties', 'idx_parties_business_name')) {
                $table->index(['business_id', 'partyName'], 'idx_parties_business_name');
            }
        });

        // Purchases Table Indexes
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasIndex('purchases', 'idx_purchases_business_date')) {
                $table->index(['business_id', 'purchaseDate'], 'idx_purchases_business_date');
            }
            if (!Schema::hasIndex('purchases', 'idx_purchases_invoice')) {
                $table->index('invoiceNumber', 'idx_purchases_invoice');
            }
        });

        // Stock Movements Indexes
        Schema::table('stock_movements', function (Blueprint $table) {
            if (!Schema::hasIndex('stock_movements', 'idx_stock_movements_business_product')) {
                $table->index(['business_id', 'product_id'], 'idx_stock_movements_business_product');
            }
            if (!Schema::hasIndex('stock_movements', 'idx_stock_movements_business_type')) {
                $table->index(['business_id', 'movement_type'], 'idx_stock_movements_business_type');
            }
            if (!Schema::hasIndex('stock_movements', 'idx_stock_movements_reference')) {
                $table->index(['reference_type', 'reference_id'], 'idx_stock_movements_reference');
            }
        });

        // Invoices Table Indexes
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasIndex('invoices', 'idx_invoices_business_date')) {
                $table->index(['business_id', 'invoiceDate'], 'idx_invoices_business_date');
            }
            if (!Schema::hasIndex('invoices', 'idx_invoices_number')) {
                $table->index('invoiceNumber', 'idx_invoices_number');
            }
        });

        // Invoice Items Indexes
        Schema::table('invoice_items', function (Blueprint $table) {
            if (!Schema::hasIndex('invoice_items', 'idx_invoice_items_invoice')) {
                $table->index('invoice_id', 'idx_invoice_items_invoice');
            }
            if (!Schema::hasIndex('invoice_items', 'idx_invoice_items_product')) {
                $table->index('product_id', 'idx_invoice_items_product');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Products Table
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_business_status');
            $table->dropIndex('idx_products_business_category');
            $table->dropIndex('idx_products_code');
            $table->dropIndex('idx_products_name');
        });

        // Sales Table
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_business_date');
            $table->dropIndex('idx_sales_invoice');
            $table->dropIndex('idx_sales_business_paid');
        });

        // Stocks Table
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropIndex('idx_stocks_product_expire');
            $table->dropIndex('idx_stocks_batch');
            $table->dropIndex('idx_stocks_business_product');
        });

        // Sale Details
        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropIndex('idx_sale_details_sale');
            $table->dropIndex('idx_sale_details_product');
        });

        // Purchase Details
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->dropIndex('idx_purchase_details_purchase');
            $table->dropIndex('idx_purchase_details_product');
        });

        // Parties Table
        Schema::table('parties', function (Blueprint $table) {
            $table->dropIndex('idx_parties_business_type');
            $table->dropIndex('idx_parties_business_name');
        });

        // Purchases Table
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex('idx_purchases_business_date');
            $table->dropIndex('idx_purchases_invoice');
        });

        // Stock Movements
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('idx_stock_movements_business_product');
            $table->dropIndex('idx_stock_movements_business_type');
            $table->dropIndex('idx_stock_movements_reference');
        });

        // Invoices Table
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_business_date');
            $table->dropIndex('idx_invoices_number');
        });

        // Invoice Items
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex('idx_invoice_items_invoice');
            $table->dropIndex('idx_invoice_items_product');
        });
    }
};
