<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reproduces the production `prescription_refills` table, which existed in Supabase but had no
 * migration in this repository (so `migrate:fresh` could not reproduce production).
 * Derived from the live database's information_schema / pg_catalog on 2026-11-10.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('prescription_refills')) {
            return;
        }

        Schema::create('prescription_refills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('prescription_id');
            $table->integer('refill_number')->default(1);
            $table->date('refill_date')->useCurrent();
            $table->integer('quantity_dispensed')->default(0);
            $table->uuid('dispensed_by')->nullable();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->text('status')->default('completed');
            $table->text('notes')->nullable();
            $table->timestampTz('created_at', 6)->useCurrent();
            $table->timestampTz('updated_at', 6)->useCurrent();
            $table->index('prescription_id', 'idx_prescription_refills_fk_0c24dc71d2f554fb854a20b8b7e035ac');
            $table->index('dispensed_by', 'idx_prescription_refills_fk_3b35f20d36e8ef5259c2ac103027f0ac');
            $table->index('sale_id', 'idx_prescription_refills_fk_d51b3c24dea7798e00cfae0ce1ae1dac');
            $table->unique(['business_id', 'prescription_id', 'refill_number'], 'prescription_refills_business_id_prescription_id_refill_num_key');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('CASCADE');
            $table->foreign('dispensed_by')->references('id')->on('app_users')->onDelete('SET NULL');
            $table->foreign('prescription_id')->references('id')->on('prescriptions')->onDelete('CASCADE');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('SET NULL');
        });

        // Postgres-only: expression / partial indexes and expression defaults have no
        // portable Schema-builder equivalent, and are absent on sqlite (CI).
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE prescription_refills ALTER COLUMN refill_date SET DEFAULT CURRENT_DATE");
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_refills');
    }
};
