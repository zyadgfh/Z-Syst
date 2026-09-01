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
        Schema::table('invoices', function (Blueprint $table) {
            // E-Invoicing QR code fields
            $table->text('qr_code')->nullable()->after('notes');
            $table->json('qr_code_data')->nullable()->after('qr_code');
            $table->string('einvoice_status')->nullable()->after('qr_code_data');
            $table->string('einvoice_submission_id')->nullable()->after('einvoice_status');
            $table->timestamp('einvoice_submitted_at')->nullable()->after('einvoice_submission_id');
            $table->string('einvoice_compliance_hash')->nullable()->after('einvoice_submitted_at');
            $table->boolean('is_einvoice_compliant')->default(false)->after('einvoice_compliance_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'qr_code',
                'qr_code_data',
                'einvoice_status',
                'einvoice_submission_id',
                'einvoice_submitted_at',
                'einvoice_compliance_hash',
                'is_einvoice_compliant',
            ]);
        });
    }
};