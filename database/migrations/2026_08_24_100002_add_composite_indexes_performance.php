<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Products
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasIndex('products', 'idx_products_business_status')) {
                $table->index(['business_id', 'status'], 'idx_products_business_status');
            }
            if (!Schema::hasIndex('products', 'idx_products_business_category')) {
                $table->index(['business_id', 'category_id'], 'idx_products_business_category');
            }
            if (!Schema::hasIndex('products', 'idx_products_active')) {
                $table->index(['business_id', 'active', 'archived'], 'idx_products_active');
            }
            if (!Schema::hasIndex('products', 'idx_products_code')) {
                $table->index('productCode', 'idx_products_code');
            }
            if (!Schema::hasIndex('products', 'idx_products_barcode')) {
                $table->index('barcode', 'idx_products_barcode');
            }
        });

        // Sales
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
            if (!Schema::hasIndex('sales', 'idx_sales_business_party')) {
                $table->index(['business_id', 'party_id'], 'idx_sales_business_party');
            }
        });

        // Stocks
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

        // Parties
        Schema::table('parties', function (Blueprint $table) {
            if (!Schema::hasIndex('parties', 'idx_parties_business_type')) {
                $table->index(['business_id', 'partyType'], 'idx_parties_business_type');
            }
            if (!Schema::hasIndex('parties', 'idx_parties_business_name')) {
                $table->index(['business_id', 'partyName'], 'idx_parties_business_name');
            }
        });

        // Sale Details
        Schema::table('sale_details', function (Blueprint $table) {
            if (!Schema::hasIndex('sale_details', 'idx_sale_details_sale')) {
                $table->index('sale_id', 'idx_sale_details_sale');
            }
            if (!Schema::hasIndex('sale_details', 'idx_sale_details_product')) {
                $table->index('product_id', 'idx_sale_details_product');
            }
        });

        // Purchase Details
        Schema::table('purchase_details', function (Blueprint $table) {
            if (!Schema::hasIndex('purchase_details', 'idx_purchase_details_purchase')) {
                $table->index('purchase_id', 'idx_purchase_details_purchase');
            }
            if (!Schema::hasIndex('purchase_details', 'idx_purchase_details_product')) {
                $table->index('product_id', 'idx_purchase_details_product');
            }
        });

        // Categories
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasIndex('categories', 'idx_categories_business_status')) {
                $table->index(['business_id', 'status'], 'idx_categories_business_status');
            }
        });

        // Units
        Schema::table('units', function (Blueprint $table) {
            if (!Schema::hasIndex('units', 'idx_units_business_status')) {
                $table->index(['business_id', 'status'], 'idx_units_business_status');
            }
        });

        // Taxes
        Schema::table('taxes', function (Blueprint $table) {
            if (!Schema::hasIndex('taxes', 'idx_taxes_business')) {
                $table->index(['business_id'], 'idx_taxes_business');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex([
                'idx_products_business_status',
                'idx_products_business_category',
                'idx_products_active',
                'idx_products_code',
                'idx_products_barcode',
            ]);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex([
                'idx_sales_business_date',
                'idx_sales_invoice',
                'idx_sales_business_paid',
                'idx_sales_business_party',
            ]);
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropIndex([
                'idx_stocks_product_expire',
                'idx_stocks_batch',
                'idx_stocks_business_product',
            ]);
        });

        Schema::table('parties', function (Blueprint $table) {
            $table->dropIndex([
                'idx_parties_business_type',
                'idx_parties_business_name',
            ]);
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropIndex(['idx_sale_details_sale', 'idx_sale_details_product']);
        });

        Schema::table('purchase_details', function (Blueprint $table) {
            $table->dropIndex(['idx_purchase_details_purchase', 'idx_purchase_details_product']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('idx_categories_business_status');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropIndex('idx_units_business_status');
        });

        Schema::table('taxes', function (Blueprint $table) {
            $table->dropIndex('idx_taxes_business');
        });
    }
};
