<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the missing "branches" table.
 *
 * Several 2026_08_07_* migrations declare foreignId('branch_id')
 * ->constrained() (barcodes, purchase_orders, supplier_invoices,
 * doctor_attention, grn, supplier_management, payment_tracking, budget,
 * quality_standards, tenant_payment_settings), but no migration ever
 * created the referenced table. On sqlite this made any INSERT into those
 * tables fail with "no such table: main.branches" (FK target missing), and
 * on MySQL the constrained CREATE TABLE statements could not have run
 * either. This restores the intended multi-branch feature foundation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('branch_id')->nullable(); // parent branch (hierarchy)
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
