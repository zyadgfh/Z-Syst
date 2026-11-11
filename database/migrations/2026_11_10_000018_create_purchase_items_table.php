<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reproduces the production `purchase_items` table, which existed in Supabase but had no
 * migration in this repository (so `migrate:fresh` could not reproduce production).
 * Derived from the live database's information_schema / pg_catalog on 2026-11-10.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_items')) {
            return;
        }

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('purchase_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity')->default(0);
            $table->decimal('unit_price', 14, 4)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->text('batch_no')->nullable();
            $table->date('expire_date')->nullable();
            $table->timestampTz('created_at', 6)->useCurrent();
            $table->timestampTz('updated_at', 6)->useCurrent();
            $table->index('product_id', 'idx_purchase_items_fk_1f5792707685be146c24ed223caefa64');
            $table->index('purchase_id', 'idx_purchase_items_fk_855a79ec6aca6151d3bbdfc606696a9d');
            $table->index(['business_id', 'purchase_id'], 'purchase_items_business_purchase_idx');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('CASCADE');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('RESTRICT');
            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('CASCADE');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
}
