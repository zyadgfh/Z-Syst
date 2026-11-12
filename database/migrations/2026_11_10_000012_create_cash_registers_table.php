<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reproduces the production `cash_registers` table, which existed in Supabase but had no
 * migration in this repository (so `migrate:fresh` could not reproduce production).
 * Derived from the live database's information_schema / pg_catalog on 2026-11-10.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cash_registers')) {
            return;
        }

        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->uuid('opened_by')->nullable();
            $table->uuid('closed_by')->nullable();
            $table->text('status')->default('open');
            $table->timestampTz('opened_at', 6)->useCurrent();
            $table->timestampTz('closed_at', 6)->nullable();
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('closing_balance', 14, 2)->nullable();
            $table->decimal('expected_balance', 14, 2)->nullable();
            $table->decimal('cash_sales', 14, 2)->default(0);
            $table->decimal('cash_refunds', 14, 2)->default(0);
            $table->decimal('cash_in', 14, 2)->default(0);
            $table->decimal('cash_out', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestampTz('created_at', 6)->useCurrent();
            $table->timestampTz('updated_at', 6)->useCurrent();
            $table->index('branch_id', 'cash_registers_branch_id_idx');
            $table->index('closed_by', 'cash_registers_closed_by_idx');
            $table->index('opened_by', 'cash_registers_opened_by_idx');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('SET NULL');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('CASCADE');
            $table->foreign('closed_by')->references('id')->on('app_users')->onDelete('SET NULL');
            $table->foreign('opened_by')->references('id')->on('app_users')->onDelete('SET NULL');
        });

        // Postgres-only: expression / partial indexes and expression defaults have no
        // portable Schema-builder equivalent, and are absent on sqlite (CI).
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS cash_registers_one_open_per_branch ON cash_registers (business_id, COALESCE(branch_id, 0::bigint)) WHERE status = 'open'");
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};
