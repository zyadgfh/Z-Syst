<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_company_id')->constrained('insurance_companies')->cascadeOnDelete();
            $table->foreignId('insurance_policy_id')->constrained('insurance_policies')->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('prescription_id')->nullable()->constrained('prescriptions')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('claim_number')->unique();
            $table->date('service_date');
            $table->date('submission_date')->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->decimal('covered_amount', 12, 2)->default(0);
            $table->decimal('patient_responsibility', 12, 2)->default(0);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('rejected_amount', 12, 2)->default(0);
            $table->enum('status', [
                'draft', 'submitted', 'under_review', 'approved',
                'partially_approved', 'rejected', 'paid', 'cancelled',
            ])->default('draft');
            $table->string('rejection_reason')->nullable();
            $table->string('external_reference')->nullable(); // insurer's claim id
            $table->date('settlement_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('line_items')->nullable(); // array of items with prices, coverage, etc.
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'service_date']);
            $table->index(['insurance_company_id', 'status']);
            $table->index('external_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_claims');
    }
};
