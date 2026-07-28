<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('controlled_substances_log')) {
            return;
        }
        Schema::create('controlled_substances_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->uuid('branch_id')->comment('معرف الفرع');
            $table->uuid('product_id')->comment('معرف المنتج');
            $table->string('batch_number', 100)->nullable()->comment('رقم الدفعة');
            $table->string('movement_type', 50)->comment('نوع الحركة');
            $table->decimal('quantity', 15, 3)->default(0)->comment('الكمية');
            $table->uuid('prescription_id')->nullable()->comment('معرف الوصفة');
            $table->uuid('patient_id')->nullable()->comment('معرف المريض');
            $table->uuid('doctor_id')->nullable()->comment('معرف الطبيب');
            $table->uuid('performed_by')->comment('منفذ بواسطة');
            $table->timestamp('performed_at')->useCurrent()->comment('تاريخ التنفيذ');
            $table->uuid('witness_id')->nullable()->comment('شهود');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->string('regulatory_reference', 255)->nullable()->comment('المرجع التنظيمي');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('company_id', 'fk_controlled_log_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('branch_id', 'fk_controlled_log_branch_id')
                  ->references('id')->on('branches')
                  ->onDelete('cascade');
            $table->foreign('product_id', 'fk_controlled_log_product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');
            $table->foreign('prescription_id', 'fk_controlled_log_prescription_id')
                  ->references('id')->on('prescriptions')
                  ->onDelete('set null');
            $table->foreign('patient_id', 'fk_controlled_log_patient_id')
                  ->references('id')->on('customers')
                  ->onDelete('set null');
            $table->foreign('doctor_id', 'fk_controlled_log_doctor_id')
                  ->references('id')->on('doctors')
                  ->onDelete('set null');
            $table->foreign('performed_by', 'fk_controlled_log_performed_by')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
            $table->foreign('witness_id', 'fk_controlled_log_witness_id')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Indexes
            $table->index('product_id', 'idx_controlled_log_product');
            $table->index('patient_id', 'idx_controlled_log_patient');
            $table->index('performed_at', 'idx_controlled_log_performed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controlled_substances_log');
    }
};