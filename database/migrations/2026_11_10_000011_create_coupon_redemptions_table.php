<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reproduces the production `coupon_redemptions` table, which existed in Supabase but had no
 * migration in this repository (so `migrate:fresh` could not reproduce production).
 * Derived from the live database's information_schema / pg_catalog on 2026-11-10.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('coupon_redemptions')) {
            return;
        }

        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('party_id')->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->timestampTz('redeemed_at', 6)->useCurrent();
            $table->uuid('created_by')->nullable();
            $table->index(['business_id', 'coupon_id', 'party_id'], 'coupon_redemptions_business_coupon_party_idx');
            $table->unique(['business_id', 'coupon_id', 'sale_id'], 'coupon_redemptions_business_coupon_sale_uidx');
            $table->index('party_id', 'idx_coupon_redemptions_fk_96860ecdb5011537177e0d5db8c89256');
            $table->index('sale_id', 'idx_coupon_redemptions_fk_d4078be88b1d2d62f0e773a73e906119');
            $table->index('coupon_id', 'idx_coupon_redemptions_fk_dd52fce2c68053d322743b044b4af443');
            $table->index('created_by', 'idx_coupon_redemptions_fk_f0c2fc98900134537075c307e9357150');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('CASCADE');
            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('RESTRICT');
            $table->foreign('created_by')->references('id')->on('app_users')->onDelete('SET NULL');
            $table->foreign('party_id')->references('id')->on('parties')->onDelete('SET NULL');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('CASCADE');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
    }
};
