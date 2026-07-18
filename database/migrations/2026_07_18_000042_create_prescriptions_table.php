<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->uuid('branch_id')->comment('معرف الفرع');
            $table->uuid('patient_id')->comment('معرف المريض');
            $table->uuid('doctor_id')->nullable()->comment('معرف الطبيب');
            $table->string('prescription_number', 50)->comment('رقم الوصفة');
            $table->date('prescribed_date')->comment('تاريخ الوصفة');
            $table->date('expiry_date')->comment('تاريخ الانتهاء');
            $table->string('status', 50)->default('pending')->comment('الحالة');
            $table->string('priority', 20)->default('normal')->comment('الأولوية');
            $table->string('image_path', 500)->nullable()->comment('مسار الصورة');
            $table->text('diagnosis')->nullable()->comment('التشخيص');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->integer('refills_allowed')->default(0)->comment('إعادة التعبئة مسموحة');
            $table->integer('refills_used')->default(0)->comment('إعادة التعبئة المستخدمة');
            $table->decimal('total_amount', 15, 3)->default(0)->comment('الإجمالي');
            $table->uuid('created_by')->nullable()->comment('منشئ');
            $table->uuid('dispensed_by')->nullable()->comment('صرف بواسطة');
            $table->timestamp('dispensed_at')->nullable()->comment('تاريخ الصرف');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_prescriptions_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('branch_id', 'fk_prescriptions_branch_id')
                  ->references('id')->on('branches')
                  ->onDelete('cascade');
            $table->foreign('patient_id', 'fk_prescriptions_patient_id')
                  ->references('id')->on('customers')
                  ->onDelete('cascade');
            $table->foreign('doctor_id', 'fk_prescriptions_doctor_id')
                  ->references('id')->on('doctors')
                  ->onDelete('set null');
            $table->foreign('created_by', 'fk_prescriptions_created_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
            $table->foreign('dispensed_by', 'fk_prescriptions_dispensed_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Unique Constraints
            $table->unique(['company_id', 'prescription_number'], 'uniq_prescriptions_number_company');

            // Indexes
            $table->index('company_id', 'idx_prescriptions_company_id');
            $table->index('branch_id', 'idx_prescriptions_branch_id');
            $table->index('patient_id', 'idx_prescriptions_patient_id');
            $table->index('doctor_id', 'idx_prescriptions_doctor_id');
            $table->index('status', 'idx_prescriptions_status');
            $table->index('prescribed_date', 'idx_prescriptions_prescribed_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};