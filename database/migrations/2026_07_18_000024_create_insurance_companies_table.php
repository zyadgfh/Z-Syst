<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('insurance_companies')) {
            return;
        }
        Schema::create('insurance_companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->string('name', 255)->comment('اسم شركة التأمين');
            $table->string('code', 50)->comment('الكود');
            $table->string('contact_person', 255)->nullable()->comment('شخص التواصل');
            $table->string('phone', 50)->nullable()->comment('الهاتف');
            $table->string('email', 255)->nullable()->comment('البريد الإلكتروني');
            $table->text('address')->nullable()->comment('العنوان');
            $table->string('contract_number', 100)->nullable()->comment('رقم العقد');
            $table->date('contract_start')->nullable()->comment('بدء العقد');
            $table->date('contract_end')->nullable()->comment('نهاية العقد');
            $table->decimal('discount_percentage', 5, 2)->default(0)->comment('نسبة الخصم');
            $table->string('payment_terms', 100)->nullable()->comment('شروط الدفع');
            $table->decimal('credit_limit', 15, 3)->default(0)->comment('حد الائتمان');
            $table->decimal('current_balance', 15, 3)->default(0)->comment('الرصيد الحالي');
            $table->boolean('is_active')->default(true)->comment('نشط');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_insurance_companies_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');

            // Unique Constraints
            $table->unique(['company_id', 'code'], 'uniq_insurance_companies_code');

            // Indexes
            $table->index('company_id', 'idx_insurance_companies_company_id');
            $table->index('is_active', 'idx_insurance_companies_is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_companies');
    }
};