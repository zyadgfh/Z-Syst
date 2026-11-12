<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reproduces the production `financial_transactions` table, which existed in Supabase but had no
 * migration in this repository (so `migrate:fresh` could not reproduce production).
 * Derived from the live database's information_schema / pg_catalog on 2026-11-10.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('financial_transactions')) {
            return;
        }

        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->text('transaction_type');
            $table->text('direction');
            $table->text('payment_method');
            $table->decimal('amount');
            $table->text('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('register_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->jsonb('metadata');
            $table->timestampTz('created_at', 6)->useCurrent();
            $table->timestampTz('updated_at', 6)->nullable();
            $table->index(['business_id', 'reference_type', 'reference_id'], 'financial_transactions_business_reference_idx');
            $table->index('created_by', 'financial_transactions_created_by_idx');
            $table->index('register_id', 'financial_transactions_register_id_idx');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('CASCADE');
            $table->foreign('created_by')->references('id')->on('app_users')->onDelete('SET NULL');
            $table->foreign('register_id')->references('id')->on('cash_registers')->onDelete('SET NULL');
        });

        // Postgres-only: expression / partial indexes and expression defaults have no
        // portable Schema-builder equivalent, and are absent on sqlite (CI).
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE financial_transactions ALTER COLUMN amount TYPE numeric");
            DB::statement("CREATE INDEX IF NOT EXISTS financial_transactions_business_created_idx ON financial_transactions (business_id, created_at DESC)");
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS financial_transactions_source_unique_idx ON financial_transactions (business_id, transaction_type, reference_type, reference_id, direction, payment_method) WHERE reference_id IS NOT NULL");
            DB::statement("ALTER TABLE financial_transactions ALTER COLUMN metadata SET DEFAULT '{}'::jsonb");
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
