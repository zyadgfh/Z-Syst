<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reproduces the production `sale_items` table, which existed in Supabase but had no
 * migration in this repository (so `migrate:fresh` could not reproduce production).
 * Derived from the live database's information_schema / pg_catalog on 2026-11-10.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sale_items')) {
            return;
        }

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity')->default(0);
            $table->decimal('unit_price', 14, 4)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->text('batch_no')->nullable();
            $table->timestampTz('created_at', 6)->useCurrent();
            $table->timestampTz('updated_at', 6)->useCurrent();
            $table->index('product_id', 'idx_sale_items_fk_73ca7459bc8c6cd61e43603b01a0d715');
            $table->index('sale_id', 'idx_sale_items_fk_9edf04db84670aa43220657470c10bb2');
            $table->index(['business_id', 'sale_id'], 'sale_items_business_sale_idx');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('CASCADE');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('RESTRICT');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('CASCADE');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
}
