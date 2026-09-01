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
            // E-Prescription fields
            $table->string('external_prescription_id')->nullable()->unique()->after('meta');
            $table->string('eprescription_status')->nullable()->after('external_prescription_id');
            $table->timestamp('submitted_at')->nullable()->after('eprescription_status');
            $table->timestamp('eprescription_synced_at')->nullable()->after('submitted_at');
            
            // Digital signature
            $table->text('digital_signature')->nullable()->after('eprescription_synced_at');
            $table->timestamp('signed_at')->nullable()->after('digital_signature');
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete()->after('signed_at');
            
            // FHIR resource reference
            $table->string('fhir_resource_id')->nullable()->after('signed_by');
            $table->json('fhir_resource')->nullable()->after('fhir_resource_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn([
                'external_prescription_id',
                'eprescription_status',
                'submitted_at',
                'eprescription_synced_at',
                'digital_signature',
                'signed_at',
                'signed_by',
                'fhir_resource_id',
                'fhir_resource',
            ]);
        });
    }
};