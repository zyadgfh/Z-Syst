<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reproduces the production `cash_register_transactions` table, which existed in Supabase but had no
 * migration in this repository (so `migrate:fresh` could not reproduce production).
 * Derived from the live database's information_schema / pg_catalog on 2026-11-10.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cash_register_transactions')) {
            return;
        }

        Schema::create('cash_register_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('register_id');
            $table->text('type');
            $table->decimal('amount', 14, 2);
            $table->text('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestampTz('created_at', 6)->useCurrent();
            $table->timestampTz('updated_at', 6)->nullable();
            $table->index(['business_id', 'register_id', 'created_at'], 'cash_register_transactions_business_register_created_idx');
            $table->index('created_by', 'cash_register_transactions_created_by_idx');
            $table->index('register_id', 'cash_register_transactions_register_id_idx');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('CASCADE');
            $table->foreign('created_by')->references('id')->on('app_users')->onDelete('SET NULL');
            $table->foreign('register_id')->references('id')->on('cash_registers')->onDelete('CASCADE');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('cash_register_transactions');
    }
}
