<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->uuid('branch_id')->nullable()->comment('معرف الفرع');
            $table->string('payable_type', 50)->comment('نوع المستحق');
            $table->uuid('payable_id')->comment('معرف المستحق');
            $table->string('payment_type', 50)->comment('نوع الدفع');
            $table->decimal('amount', 15, 3)->default(0)->comment('المبلغ');
            $table->string('payment_method', 50)->nullable()->comment('طريقة الدفع');
            $table->string('reference_number', 255)->nullable()->comment('رقم المرجع');
            $table->date('payment_date')->comment('تاريخ الدفع');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->uuid('recorded_by')->nullable()->comment('مسجل بواسطة');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('company_id', 'fk_payment_records_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('branch_id', 'fk_payment_records_branch_id')
                  ->references('id')->on('branches')
                  ->onDelete('set null');
            $table->foreign('recorded_by', 'fk_payment_records_recorded_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Indexes
            $table->index('company_id', 'idx_payment_records_company_id');
            $table->index('branch_id', 'idx_payment_records_branch_id');
            $table->index('payable_type', 'idx_payment_records_payable_type');
            $table->index('payable_id', 'idx_payment_records_payable_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_records');
    }
};