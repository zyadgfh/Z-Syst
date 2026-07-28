<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('doctors')) {
            return;
        }
        Schema::create('doctors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->string('doctor_code', 50)->comment('كود الطبيب');
            $table->string('first_name', 100)->comment('الاسم الأول');
            $table->string('last_name', 100)->comment('الاسم الأخير');
            $table->string('specialization', 255)->nullable()->comment('التخصص');
            $table->string('license_number', 100)->nullable()->comment('رقم الرخصة');
            $table->date('license_expiry')->nullable()->comment('انتهاء الرخصة');
            $table->string('clinic_name', 255)->nullable()->comment('اسم العيادة');
            $table->text('clinic_address')->nullable()->comment('عنوان العيادة');
            $table->string('phone', 50)->nullable()->comment('الهاتف');
            $table->string('phone_secondary', 50)->nullable()->comment('هاتف ثانوي');
            $table->string('email', 255)->nullable()->comment('البريد الإلكتروني');
            $table->decimal('commission_percentage', 5, 2)->default(0)->comment('نسبة العمولة');
            $table->integer('total_prescriptions')->default(0)->comment('إجمالي الوصفات');
            $table->boolean('is_active')->default(true)->comment('نشط');
            $table->uuid('created_by')->nullable()->comment('منشئ');
            $table->uuid('updated_by')->nullable()->comment('محدث');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_doctors_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('created_by', 'fk_doctors_created_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
            $table->foreign('updated_by', 'fk_doctors_updated_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Unique Constraints
            $table->unique(['company_id', 'doctor_code'], 'uniq_doctors_code_company');

            // Indexes
            $table->index('company_id', 'idx_doctors_company_id');
            $table->index('specialization', 'idx_doctors_specialization');
            $table->index('is_active', 'idx_doctors_is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};