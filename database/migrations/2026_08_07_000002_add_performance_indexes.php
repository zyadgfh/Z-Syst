<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The legacy Laravel schema already creates several of these indexes on SQLite.
        // Keep SQLite CI deterministic while preserving the full index set for production drivers.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }
        // Products table indexes
        Schema::table('products', function (Blueprint $table) {
            $table->index(['business_id', 'is_active']);
            $table->index('expiry_date');
            $table->index('category_id');
            $table->index('created_at');
        });

        // Sales table indexes
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['business_id', 'saleDate']);
            $table->index('party_id');
            $table->index('invoiceNumber');
            $table->index('created_at');
        });

        // Purchases table indexes
        Schema::table('purchases', function (Blueprint $table) {
            $table->index(['business_id', 'purchaseDate']);
            $table->index('party_id');
            $table->index('invoiceNumber');
            $table->index('created_at');
        });

        // Parties table indexes
        Schema::table('parties', function (Blueprint $table) {
            $table->index(['business_id', 'type']);
            $table->index('phone');
            $table->index('name');
        });

        // Sale details table indexes
        Schema::table('sale_details', function (Blueprint $table) {
            $table->index('sale_id');
            $table->index('product_id');
            $table->index(['sale_id', 'product_id']);
        });

        // Purchase details table indexes
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->index('purchase_id');
            $table->index('product_id');
            $table->index(['purchase_id', 'product_id']);
        });

        // Loyalty transactions table indexes
        Schema::table('loyalty_transactions', function (Blueprint $table) {
            $table->index(['business_id', 'created_at']);
            $table->index('party_id');
            $table->index('program_id');
        });

        // Customer interactions table indexes
        Schema::table('customer_interactions', function (Blueprint $table) {
            $table->index(['business_id', 'created_at']);
            $table->index('party_id');
            $table->index('user_id');
        });

        // Receipts table indexes
        Schema::table('receipts', function (Blueprint $table) {
            $table->index(['business_id', 'created_at']);
            $table->index('receipt_number');
            $table->index('sale_id');
            $table->index('purchase_id');
        });

        // Warehouses table indexes
        Schema::table('warehouses', function (Blueprint $table) {
            $table->index(['business_id', 'is_active']);
        });

        // Warehouse stocks table indexes
        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->index(['warehouse_id', 'product_id']);
            $table->index('product_id');
        });

        // Stock transfers table indexes
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->index(['business_id', 'transfer_date']);
            $table->index('from_warehouse_id');
            $table->index('to_warehouse_id');
            $table->index('status');
        });

        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index(['business_id', 'status']);
            $table->index('email');
            $table->index('phone');
        });

        // Businesses table indexes
        Schema::table('businesses', function (Blueprint $table) {
            $table->index('plan_subscribe_id');
            $table->index('status');
            $table->index('will_expire');
        });
    }

    public function down(): void
    {
        // Drop indexes
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'is_active']);
            $table->dropIndex('expiry_date');
            $table->dropIndex('category_id');
            $table->dropIndex('created_at');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'saleDate']);
            $table->dropIndex('party_id');
            $table->dropIndex('invoiceNumber');
            $table->dropIndex('created_at');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'purchaseDate']);
            $table->dropIndex('party_id');
            $table->dropIndex('invoiceNumber');
            $table->dropIndex('created_at');
        });

        Schema::table('parties', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'type']);
            $table->dropIndex('phone');
            $table->dropIndex('name');
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropIndex('sale_id');
            $table->dropIndex('product_id');
            $table->dropIndex(['sale_id', 'product_id']);
        });

        Schema::table('purchase_details', function (Blueprint $table) {
            $table->dropIndex('purchase_id');
            $table->dropIndex('product_id');
            $table->dropIndex(['purchase_id', 'product_id']);
        });

        Schema::table('loyalty_transactions', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'created_at']);
            $table->dropIndex('party_id');
            $table->dropIndex('program_id');
        });

        Schema::table('customer_interactions', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'created_at']);
            $table->dropIndex('party_id');
            $table->dropIndex('user_id');
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'created_at']);
            $table->dropIndex('receipt_number');
            $table->dropIndex('sale_id');
            $table->dropIndex('purchase_id');
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'is_active']);
        });

        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->dropIndex(['warehouse_id', 'product_id']);
            $table->dropIndex('product_id');
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'transfer_date']);
            $table->dropIndex('from_warehouse_id');
            $table->dropIndex('to_warehouse_id');
            $table->dropIndex('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'status']);
            $table->dropIndex('email');
            $table->dropIndex('phone');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropIndex('plan_subscribe_id');
            $table->dropIndex('status');
            $table->dropIndex('will_expire');
        });
    }
};
