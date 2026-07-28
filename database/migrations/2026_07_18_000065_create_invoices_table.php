<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoices')) {
            return;
        }
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->uuid('subscription_id')->nullable()->comment('معرف الاشتراك');
            $table->string('invoice_number', 50)->unique()->comment('رقم الفاتورة');
            $table->date('invoice_date')->comment('تاريخ الفاتورة');
            $table->date('due_date')->nullable()->comment('تاريخ الاستحقاق');
            $table->decimal('subtotal', 15, 3)->default(0)->comment('الإجمالي الفرعي');
            $table->decimal('tax', 15, 3)->default(0)->comment('الضريبة');
            $table->decimal('total', 15, 3)->default(0)->comment('الإجمالي');
            $table->decimal('amount_paid', 15, 3)->default(0)->comment('المبلغ المدفوع');
            $table->string('status', 50)->default('draft')->comment('الحالة');
            $table->string('pdf_path', 500)->nullable()->comment('مسار PDF');
            $table->string('stripe_invoice_id', 255)->nullable()->comment('معرف فاتورة Stripe');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_invoices_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('subscription_id', 'fk_invoices_subscription_id')
                  ->references('id')->on('subscriptions')
                  ->onDelete('set null');

            // Indexes
            $table->index('company_id', 'idx_invoices_company_id');
            $table->index('subscription_id', 'idx_invoices_subscription_id');
            $table->index('status', 'idx_invoices_status');
            $table->index('invoice_date', 'idx_invoices_invoice_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};