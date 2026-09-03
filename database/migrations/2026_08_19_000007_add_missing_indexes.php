<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Products - search by name
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasIndex('products', 'idx_products_name')) {
                $table->index('productName', 'idx_products_name');
            }
            if (!Schema::hasIndex('products', 'idx_products_type_id')) {
                $table->index('type_id', 'idx_products_type_id');
            }
            if (!Schema::hasIndex('products', 'idx_products_manufacturer_id')) {
                $table->index('manufacturer_id', 'idx_products_manufacturer_id');
            }
        });

        // Sales - search by invoice number and date reporting
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasIndex('sales', 'idx_sales_invoice_number')) {
                $table->index('invoiceNumber', 'idx_sales_invoice_number');
            }
            if (!Schema::hasIndex('sales', 'idx_sales_sale_date')) {
                $table->index('saleDate', 'idx_sales_sale_date');
            }
        });

        // Purchases - search by invoice number and date reporting
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasIndex('purchases', 'idx_purchases_invoice_number')) {
                $table->index('invoiceNumber', 'idx_purchases_invoice_number');
            }
            if (!Schema::hasIndex('purchases', 'idx_purchases_purchase_date')) {
                $table->index('purchaseDate', 'idx_purchases_purchase_date');
            }
        });

        // Expenses - date reporting
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasIndex('expenses', 'idx_expenses_date')) {
                $table->index('expenseDate', 'idx_expenses_date');
            }
        });

        // Incomes - date reporting
        Schema::table('incomes', function (Blueprint $table) {
            if (!Schema::hasIndex('incomes', 'idx_incomes_date')) {
                $table->index('incomeDate', 'idx_incomes_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_name');
            $table->dropIndex('idx_products_type_id');
            $table->dropIndex('idx_products_manufacturer_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_invoice_number');
            $table->dropIndex('idx_sales_sale_date');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex('idx_purchases_invoice_number');
            $table->dropIndex('idx_purchases_purchase_date');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('idx_expenses_date');
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->dropIndex('idx_incomes_date');
        });
    }
};
