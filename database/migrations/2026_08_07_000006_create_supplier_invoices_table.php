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
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date');
            
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            
            $table->string('status')->default('pending'); // pending, approved, partially_paid, paid, overdue, cancelled, rejected
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance', 10, 2)->default(0);
            
            $table->string('currency')->default('SAR');
            $table->string('payment_terms')->default('net_30');
            
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_mime_type')->nullable();
            
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['business_id', 'branch_id']);
            $table->index('supplier_id');
            $table->index('purchase_id');
            $table->index('purchase_order_id');
            $table->index('status');
            $table->index('invoice_date');
            $table->index('due_date');
            $table->index('created_by');
            $table->index('approved_by');
            $table->index('invoice_number');
        });

        Schema::create('supplier_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_invoice_id')->constrained('supplier_invoices')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_detail_id')->nullable()->constrained('purchase_details')->nullOnDelete();
            
            $table->string('description')->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('supplier_invoice_id');
            $table->index('product_id');
            $table->index('purchase_detail_id');
        });

        Schema::create('supplier_invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_invoice_id')->constrained('supplier_invoices')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('payment_number')->unique();
            $table->date('payment_date');
            $table->string('payment_method');
            $table->string('payment_reference')->nullable();
            $table->string('bank_reference')->nullable();
            
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // pending, approved, completed, cancelled
            $table->text('notes')->nullable();
            
            $table->timestamp('approved_at')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_mime_type')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('supplier_invoice_id');
            $table->index(['business_id', 'branch_id']);
            $table->index('status');
            $table->index('payment_date');
            $table->index('payment_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_invoice_payments');
        Schema::dropIfExists('supplier_invoice_items');
        Schema::dropIfExists('supplier_invoices');
    }
};
