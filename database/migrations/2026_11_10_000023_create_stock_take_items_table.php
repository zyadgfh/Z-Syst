<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reproduces the production `stock_take_items` table, which existed in Supabase but had no
 * migration in this repository (so `migrate:fresh` could not reproduce production).
 * Derived from the live database's information_schema / pg_catalog on 2026-11-10.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_take_items')) {
            return;
        }

        Schema::create('stock_take_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('stock_take_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('stock_id')->nullable();
            $table->integer('system_quantity')->default(0);
            $table->integer('counted_quantity')->default(0);
            // GENERATED ALWAYS AS ... STORED in production; not expressible in the
            // schema builder, so Postgres gets it as raw DDL below (sqlite gets a
            // plain nullable column so the column still exists for the test suite).
            if (DB::getDriverName() !== 'pgsql') {
                $table->integer('variance')->nullable();
            }
            $table->text('notes')->nullable();
            $table->timestampTz('created_at', 6)->useCurrent();
            $table->timestampTz('updated_at', 6)->useCurrent();
            $table->index('business_id', 'idx_stock_take_items_fk_30f44ac878a5f8b84f03e8f098ba403a');
            $table->index('product_id', 'idx_stock_take_items_fk_3dbdf2a518ea901b18abbec02ebe7ccb');
            $table->index('stock_take_id', 'idx_stock_take_items_fk_81cf02c36194dd285f51e03e0e82c635');
            $table->index('stock_id', 'idx_stock_take_items_fk_c67f817ef579541cf3e35f5bdc585102');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('CASCADE');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('NO ACTION');
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('SET NULL');
            $table->foreign('stock_take_id')->references('id')->on('stock_takes')->onDelete('CASCADE');
        });

        // Postgres-only: expression / partial indexes and expression defaults have no
        // portable Schema-builder equivalent, and are absent on sqlite (CI).
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE stock_take_items ADD COLUMN IF NOT EXISTS variance integer GENERATED ALWAYS AS (counted_quantity - system_quantity) STORED");
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('stock_take_items');
    }
}
