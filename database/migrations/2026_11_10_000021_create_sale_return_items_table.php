<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reproduces the production `sale_return_items` table, which existed in Supabase but had no
 * migration in this repository (so `migrate:fresh` could not reproduce production).
 * Derived from the live database's information_schema / pg_catalog on 2026-11-10.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sale_return_items')) {
            return;
        }

        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('sale_return_id');
            $table->unsignedBigInteger('sale_item_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('stock_id')->nullable();
            $table->integer('quantity');
            $table->decimal('refund_amount', 14, 2)->default(0);
            $table->text('batch_no')->nullable();
            $table->date('expire_date')->nullable();
            $table->timestampTz('created_at', 6)->useCurrent();
            $table->timestampTz('updated_at', 6)->nullable();
            $table->index('sale_return_id', 'idx_sale_return_items_fk_4bf677e4e12b6a2a09f963afbbbf3bc1');
            $table->index('stock_id', 'idx_sale_return_items_fk_a9a60ad4a0f30a0c43a38361543e502f');
            $table->index('sale_item_id', 'idx_sale_return_items_fk_debbec50a2afb407917e5c70a6f19256');
            $table->index('product_id', 'idx_sale_return_items_fk_ebe1f65091dfdcd0934652e6b12e89e6');
            $table->index(['business_id', 'product_id'], 'sale_return_items_business_product_idx');
            $table->index(['business_id', 'sale_return_id'], 'sale_return_items_business_return_idx');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('CASCADE');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('NO ACTION');
            $table->foreign('sale_item_id')->references('id')->on('sale_items')->onDelete('SET NULL');
            $table->foreign('sale_return_id')->references('id')->on('sale_returns')->onDelete('CASCADE');
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('SET NULL');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
    }
};
