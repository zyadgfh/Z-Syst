<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->string('customer_code', 50)->comment('كود العميل');
            $table->string('first_name', 100)->comment('الاسم الأول');
            $table->string('last_name', 100)->comment('الاسم الأخير');
            $table->string('national_id', 50)->nullable()->comment('الرقم القومي');
            $table->date('date_of_birth')->nullable()->comment('تاريخ الميلاد');
            $table->string('gender', 20)->nullable()->comment('الجنس');
            $table->string('phone', 50)->comment('الهاتف');
            $table->string('phone_secondary', 50)->nullable()->comment('هاتف ثانوي');
            $table->string('email', 255)->nullable()->comment('البريد الإلكتروني');
            $table->text('address')->nullable()->comment('العنوان');
            $table->string('city', 100)->nullable()->comment('المدينة');
            $table->string('blood_group', 10)->nullable()->comment('فصيلة الدم');
            $table->jsonb('allergies')->default('[]')->comment('الحساسية');
            $table->jsonb('chronic_conditions')->default('[]')->comment('الأمراض المزمنة');
            $table->jsonb('current_medications')->default('[]')->comment('الأدوية الحالية');
            $table->jsonb('medical_history')->default('{}')->comment('السجل الطبي');
            $table->uuid('insurance_id')->nullable()->comment('معرف التأمين');
            $table->string('insurance_number', 100)->nullable()->comment('رقم التأمين');
            $table->string('insurance_policy', 100)->nullable()->comment('سياسة التأمين');
            $table->integer('loyalty_points')->default(0)->comment('نقاط الولاء');
            $table->decimal('total_purchases', 15, 3)->default(0)->comment('إجمالي المشتريات');
            $table->decimal('credit_limit', 15, 3)->default(0)->comment('حد الائتمان');
            $table->decimal('current_balance', 15, 3)->default(0)->comment('الرصيد الحالي');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->boolean('is_vip')->default(false)->comment('عميل مميز');
            $table->boolean('is_active')->default(true)->comment('نشط');
            $table->uuid('created_by')->nullable()->comment('منشئ');
            $table->uuid('updated_by')->nullable()->comment('محدث');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_customers_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('insurance_id', 'fk_customers_insurance_id')
                  ->references('id')->on('insurance_companies')
                  ->onDelete('set null');
            $table->foreign('created_by', 'fk_customers_created_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
            $table->foreign('updated_by', 'fk_customers_updated_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Unique Constraints
            $table->unique(['company_id', 'customer_code'], 'uniq_customers_code_company');

            // Indexes
            $table->index('company_id', 'idx_customers_company_id');
            $table->index('phone', 'idx_customers_phone');
            $table->index('national_id', 'idx_customers_national_id');
            $table->index('insurance_id', 'idx_customers_insurance_id');
            $table->index('is_active', 'idx_customers_is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};