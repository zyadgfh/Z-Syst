<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            // Add new foreign key columns
            $table->foreignId('doctor_id')->nullable()->after('id')->constrained('doctors')->onDelete('set null');
            $table->foreignId('patient_id')->nullable()->after('doctor_id')->constrained('patients')->onDelete('set null');

            // Add refill tracking fields
            $table->integer('max_refills')->default(0)->after('is_used');
            $table->integer('refill_count')->default(0)->after('max_refills');
            $table->date('refill_expiry_date')->nullable()->after('refill_count');

            // Add controlled substance tracking
            $table->boolean('is_controlled_substance')->default(false)->after('notes');
            $table->string('schedule')->nullable()->after('is_controlled_substance'); // Schedule I, II, III, IV, V

            // Add indexes
            $table->index(['doctor_id', 'business_id']);
            $table->index(['patient_id', 'business_id']);
            $table->index('is_controlled_substance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign(['doctor_id']);
            $table->dropForeign(['patient_id']);
            $table->dropColumn(['doctor_id', 'patient_id', 'max_refills', 'refill_count', 'refill_expiry_date', 'is_controlled_substance', 'schedule']);
        });
    }
};
