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
        Schema::create('company_payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('gateway_type'); // vodafone_cash, bank_card, fawry, orange_cash, instapay, cash
            $table->boolean('is_active')->default(true);
            $table->text('config_data')->nullable(); // API keys, merchant IDs, etc.
            $table->text('branch_config_data')->nullable(); // Branch-specific overrides
            $table->decimal('transaction_fee', 8, 2)->default(0);
            $table->string('transaction_fee_type')->default('percentage'); // percentage or fixed
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Ensure unique gateway type per company/branch
            $table->unique(['company_id', 'branch_id', 'gateway_type']);
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('gateway_id')->nullable()->constrained('company_payment_gateways')->nullOnDelete();
            $table->string('gateway_type');
            $table->string('transaction_type'); // subscription, sale, refund
            $table->string('reference_id')->nullable(); // External transaction ID
            $table->string('internal_reference')->unique(); // Internal tracking ID
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EGP');
            $table->string('status')->default('pending'); // pending, completed, failed, refunded
            $table->text('payment_data')->nullable(); // Payment gateway response
            $table->text('metadata')->nullable(); // Additional transaction data
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['company_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index(['gateway_type', 'status']);
            $table->index(['transaction_type', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('company_payment_gateways');
    }
};
