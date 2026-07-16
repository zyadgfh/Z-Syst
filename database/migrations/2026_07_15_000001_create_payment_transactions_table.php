<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * جدول معاملات الدفع - يدعم جميع وسائل الدفع المحلية والعالمية
     */
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            // Payment Method Information
            $table->string('payment_method_type'); // vodafone_cash, orange_cash, cash, bnpl, etc.
            $table->string('transaction_type')->default('sale'); // sale, refund, deposit, withdrawal
            
            // Polymorphic Reference (links to sale, invoice, etc.)
            $table->nullableMorphs('reference');
            
            // Amount Information
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('EGP');
            $table->decimal('fee', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);
            
            // Status
            $table->string('status')->default('pending'); // pending, processing, completed, failed, refunded, cancelled
            
            // External References
            $table->string('external_transaction_id')->nullable()->index();
            $table->string('external_reference')->nullable()->unique();
            $table->string('payment_url')->nullable();
            
            // QR Code Support
            $table->text('qr_code_url')->nullable();
            $table->text('qr_code_data')->nullable();
            
            // Mobile Wallet Specific
            $table->string('mobile_number')->nullable();
            $table->string('wallet_provider')->nullable(); // vodafone, orange, etisalat
            
            // Callback/Webhook
            $table->string('callback_url')->nullable();
            $table->boolean('webhook_received')->default(false);
            $table->json('webhook_payload')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            
            // Timestamps
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_reason')->nullable();
            
            // Refund Information
            $table->timestamp('refunded_at')->nullable();
            $table->decimal('refund_amount', 15, 2)->nullable();
            $table->text('refund_reason')->nullable();
            
            // Audit
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('payment_method_type');
            $table->index('status');
            $table->index('mobile_number');
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'payment_method_type']);
            $table->index(['created_at']);
        });

        // BNPL Contracts Table (Buy Now Pay Later / أجل)
        Schema::create('bnpl_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained('parties')->cascadeOnDelete();
            
            // Contract Information
            $table->string('contract_number')->unique();
            $table->decimal('total_amount', 15, 2);
            $table->decimal('down_payment', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2);
            $table->integer('installments_count');
            $table->decimal('installment_amount', 15, 2);
            $table->string('frequency')->default('monthly'); // weekly, biweekly, monthly, custom
            $table->decimal('interest_rate', 5, 2)->default(0);
            
            // Status
            $table->string('status')->default('active'); // active, completed, defaulted, cancelled
            
            // Dates
            $table->date('start_date');
            $table->date('end_date');
            $table->date('next_payment_date');
            $table->integer('paid_installments')->default(0);
            
            // Guarantees
            $table->text('guarantee_notes')->nullable();
            $table->json('guarantee_documents')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('status');
            $table->index(['company_id', 'status']);
        });

        // BNPL Installments Schedule
        Schema::create('bnpl_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bnpl_contract_id')->constrained('bnpl_contracts')->cascadeOnDelete();
            $table->foreignId('payment_transaction_id')->nullable()->constrained('payment_transactions')->nullOnDelete();
            
            $table->integer('installment_number');
            $table->decimal('amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->date('due_date');
            $table->date('paid_at')->nullable();
            $table->string('status')->default('pending'); // pending, paid, overdue, partial
            
            $table->decimal('late_fee', 15, 2)->default(0);
            $table->decimal('remaining', 15, 2);
            $table->text('notes')->nullable();
            
            $table->timestamps();

            $table->index(['bnpl_contract_id', 'installment_number']);
            $table->index('due_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bnpl_installments');
        Schema::dropIfExists('bnpl_contracts');
        Schema::dropIfExists('payment_transactions');
    }
};