<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reproduces the production `controlled_substances_log` table, which existed in Supabase but had no
 * migration in this repository (so `migrate:fresh` could not reproduce production).
 * Derived from the live database's information_schema / pg_catalog on 2026-11-10.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('controlled_substances_log')) {
            return;
        }

        Schema::create('controlled_substances_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('stock_id')->nullable();
            $table->unsignedBigInteger('prescription_id')->nullable();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->text('action');
            $table->integer('quantity');
            $table->integer('previous_balance')->nullable();
            $table->integer('remaining_balance')->nullable();
            $table->text('patient_name')->nullable();
            $table->text('patient_phone')->nullable();
            $table->text('doctor_name')->nullable();
            $table->text('doctor_license')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->timestampTz('created_at', 6)->useCurrent();
            $table->timestampTz('updated_at', 6)->nullable();
            $table->index('stock_id', 'idx_controlled_substances_log_fk_4509bef68d7e290ede4ff9ea92b4ad');
            $table->index('product_id', 'idx_controlled_substances_log_fk_5b0d2e91e8d11189fa3fbe5a4bc044');
            $table->index('sale_id', 'idx_controlled_substances_log_fk_a5a67033f9da2ba19df76d0f094f91');
            $table->index('actor_user_id', 'idx_controlled_substances_log_fk_db708e6a9eed64ce2e7be153f3d924');
            $table->index('business_id', 'idx_controlled_substances_log_fk_e6531a705cfad08a57026196c1f942');
            $table->index('prescription_id', 'idx_controlled_substances_log_fk_f2d307243eec6ba04138a172484b98');
            $table->index('branch_id', 'idx_controlled_substances_log_fk_f61589c5cd9421544b01fe900fcaa1');
            $table->foreign('actor_user_id')->references('id')->on('app_users')->onDelete('SET NULL');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('SET NULL');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('CASCADE');
            $table->foreign('prescription_id')->references('id')->on('prescriptions')->onDelete('SET NULL');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('NO ACTION');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('SET NULL');
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('SET NULL');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('controlled_substances_log');
    }
};
