<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reproduces the production `coupons` table, which existed in Supabase but had no
 * migration in this repository (so `migrate:fresh` could not reproduce production).
 * Derived from the live database's information_schema / pg_catalog on 2026-11-10.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('coupons')) {
            return;
        }

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->text('code');
            $table->text('description')->nullable();
            $table->text('discount_type')->default('fixed');
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('min_order_amount', 12, 2)->default(0);
            $table->decimal('max_discount_amount', 12, 2)->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_count')->default(0);
            $table->integer('per_customer_limit')->nullable();
            $table->timestampTz('start_at', 6)->nullable();
            $table->timestampTz('end_at', 6)->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->timestampTz('created_at', 6)->useCurrent();
            $table->timestampTz('updated_at', 6)->useCurrent();
            $table->unique(['business_id', 'code'], 'coupons_business_id_code_key');
            $table->index('created_by', 'idx_coupons_fk_2e89e7468b202f7ce9878efb6a30c8e0');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('CASCADE');
            $table->foreign('created_by')->references('id')->on('app_users')->onDelete('SET NULL');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
}
