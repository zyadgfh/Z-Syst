<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('claim_number')->unique();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount_claimed', 12, 2);
            $table->decimal('amount_approved', 12, 2)->nullable();
            $table->decimal('co_pay_amount', 12, 2)->default(0);
            $table->decimal('settlement_amount', 12, 2)->nullable();
            $table->enum('status', ['pending', 'submitted', 'approved', 'rejected', 'paid', 'partially_paid'])->default('pending');
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->foreignId('submitted_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('claim_number');
            $table->index('insurance_company_id');
            $table->index('status');
            $table->index('patient_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_claims');
    }
};
