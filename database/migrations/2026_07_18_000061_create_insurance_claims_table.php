<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('insurance_claims')) {
            return;
        }
        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->string('claim_number', 50)->unique()->comment('رقم المطالبة');
            $table->uuid('sale_id')->comment('معرف البيع');
            $table->uuid('insurance_company_id')->comment('معرف شركة التأمين');
            $table->uuid('insurance_plan_id')->comment('معرف خطة التأمين');
            $table->uuid('patient_id')->comment('معرف المريض');
            $table->date('claim_date')->comment('تاريخ المطالبة');
            $table->decimal('amount_claimed', 15, 3)->default(0)->comment('المبلغ المطالب به');
            $table->decimal('amount_approved', 15, 3)->default(0)->comment('المبلغ المعتمد');
            $table->decimal('amount_paid', 15, 3)->default(0)->comment('المبلغ المدفوع');
            $table->string('status', 50)->default('submitted')->comment('الحالة');
            $table->timestamp('submitted_at')->useCurrent()->comment('تاريخ التقديم');
            $table->timestamp('reviewed_at')->nullable()->comment('تاريخ المراجعة');
            $table->timestamp('resolved_at')->nullable()->comment('تاريخ الحل');
            $table->timestamp('paid_at')->nullable()->comment('تاريخ الدفع');
            $table->text('rejection_reason')->nullable()->comment('سبب الرفض');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->uuid('submitted_by')->nullable()->comment('مقدم بواسطة');
            $table->uuid('reviewed_by')->nullable()->comment('مراجع بواسطة');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_insurance_claims_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('sale_id', 'fk_insurance_claims_sale_id')
                  ->references('id')->on('sales')
                  ->onDelete('cascade');
            $table->foreign('insurance_company_id', 'fk_insurance_claims_insurance_company_id')
                  ->references('id')->on('insurance_companies')
                  ->onDelete('cascade');
            $table->foreign('insurance_plan_id', 'fk_insurance_claims_insurance_plan_id')
                  ->references('id')->on('insurance_plans')
                  ->onDelete('cascade');
            $table->foreign('patient_id', 'fk_insurance_claims_patient_id')
                  ->references('id')->on('customers')
                  ->onDelete('cascade');
            $table->foreign('submitted_by', 'fk_insurance_claims_submitted_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
            $table->foreign('reviewed_by', 'fk_insurance_claims_reviewed_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Indexes
            $table->index('company_id', 'idx_insurance_claims_company_id');
            $table->index('sale_id', 'idx_insurance_claims_sale_id');
            $table->index('status', 'idx_insurance_claims_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_claims');
    }
};