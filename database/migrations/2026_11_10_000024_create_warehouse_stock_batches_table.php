<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reproduces the production `warehouse_stock_batches` table, which existed in Supabase but had no
 * migration in this repository (so `migrate:fresh` could not reproduce production).
 * Derived from the live database's information_schema / pg_catalog on 2026-11-10.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('warehouse_stock_batches')) {
            return;
        }

        Schema::create('warehouse_stock_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('stock_id');
            $table->integer('quantity')->default(0);
            $table->timestampTz('created_at', 6)->useCurrent();
            $table->timestampTz('updated_at', 6)->useCurrent();
            $table->index(['business_id', 'stock_id'], 'warehouse_stock_batches_business_stock_idx');
            $table->index(['business_id', 'warehouse_id', 'product_id'], 'warehouse_stock_batches_business_warehouse_product_idx');
            $table->index('product_id', 'warehouse_stock_batches_product_idx');
            $table->index('stock_id', 'warehouse_stock_batches_stock_id_idx');
            $table->unique(['warehouse_id', 'product_id', 'stock_id'], 'warehouse_stock_batches_unique');
            $table->index('warehouse_id', 'warehouse_stock_batches_warehouse_id_idx');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('CASCADE');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('CASCADE');
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('RESTRICT');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('CASCADE');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_stock_batches');
    }
}
