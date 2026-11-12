<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reproduces the production `prescription_items` table, which existed in Supabase but had no
 * migration in this repository (so `migrate:fresh` could not reproduce production).
 * Derived from the live database's information_schema / pg_catalog on 2026-11-10.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('prescription_items')) {
            return;
        }

        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('prescription_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->text('medicine_name_extracted')->nullable();
            $table->text('dosage')->nullable();
            $table->text('frequency')->nullable();
            $table->text('duration')->nullable();
            $table->integer('quantity')->default(1);
            $table->text('notes')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestampTz('created_at', 6)->useCurrent();
            $table->timestampTz('updated_at', 6)->useCurrent();
            $table->index('product_id', 'idx_prescription_items_fk_979ea4145e6a90597e4ac8cc93b0c4d4');
            $table->index('prescription_id', 'idx_prescription_items_fk_bfd5fdb0a97a2f3ce40a2216de8e1d3f');
            $table->index(['business_id', 'prescription_id'], 'prescription_items_prescription_idx');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('CASCADE');
            $table->foreign('prescription_id')->references('id')->on('prescriptions')->onDelete('CASCADE');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('SET NULL');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
    }
};
