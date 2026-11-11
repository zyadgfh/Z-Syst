<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reproduces the production `stock_takes` table, which existed in Supabase but had no
 * migration in this repository (so `migrate:fresh` could not reproduce production).
 * Derived from the live database's information_schema / pg_catalog on 2026-11-10.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_takes')) {
            return;
        }

        Schema::create('stock_takes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->text('audit_number');
            $table->text('audit_type')->default('manual');
            $table->text('status')->default('draft');
            $table->date('audit_date')->useCurrent();
            $table->timestampTz('completed_at', 6)->nullable();
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestampTz('created_at', 6)->useCurrent();
            $table->timestampTz('updated_at', 6)->useCurrent();
            $table->index('created_by', 'idx_stock_takes_fk_e415598229ecd3aca97491f531dd180b');
            $table->index('branch_id', 'idx_stock_takes_fk_fba8620da3f21fb0ede280bf61a1744f');
            $table->unique(['business_id', 'audit_number'], 'stock_takes_business_id_audit_number_key');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('SET NULL');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('CASCADE');
            $table->foreign('created_by')->references('id')->on('app_users')->onDelete('SET NULL');
        });

        // Postgres-only: expression / partial indexes and expression defaults have no
        // portable Schema-builder equivalent, and are absent on sqlite (CI).
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE stock_takes ALTER COLUMN audit_date SET DEFAULT CURRENT_DATE");
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('stock_takes');
    }
}
