<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reproduces the production `purchase_return_items` table, which existed in Supabase but had no
 * migration in this repository (so `migrate:fresh` could not reproduce production).
 * Derived from the live database's information_schema / pg_catalog on 2026-11-10.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_return_items')) {
            return;
        }

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('purchase_return_id');
            $table->unsignedBigInteger('purchase_item_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('stock_id')->nullable();
            $table->integer('quantity');
            $table->decimal('refund_amount', 14, 2)->default(0);
            $table->text('batch_no')->nullable();
            $table->date('expire_date')->nullable();
            $table->timestampTz('created_at', 6)->useCurrent();
            $table->timestampTz('updated_at', 6)->nullable();
            $table->index('product_id', 'idx_purchase_return_items_fk_2f55033d1f17f2267e3ee716cf996b63');
            $table->index('purchase_item_id', 'idx_purchase_return_items_fk_4dd81da9f9884d5c08b5625b20870447');
            $table->index('stock_id', 'idx_purchase_return_items_fk_af0d5d201c00294b1d599ff99630497b');
            $table->index('purchase_return_id', 'idx_purchase_return_items_fk_cde8a5d6ae7edd639deae6a43eee8094');
            $table->index(['business_id', 'product_id'], 'purchase_return_items_business_product_idx');
            $table->index(['business_id', 'purchase_return_id'], 'purchase_return_items_business_return_idx');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('CASCADE');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('NO ACTION');
            $table->foreign('purchase_item_id')->references('id')->on('purchase_items')->onDelete('SET NULL');
            $table->foreign('purchase_return_id')->references('id')->on('purchase_returns')->onDelete('CASCADE');
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('SET NULL');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
}
