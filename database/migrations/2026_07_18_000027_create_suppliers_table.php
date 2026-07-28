<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('suppliers')) {
            return;
        }
        Schema::create('suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->string('supplier_code', 50)->comment('كود المورد');
            $table->string('name', 255)->comment('اسم المورد');
            $table->string('legal_name', 255)->nullable()->comment('الاسم القانوني');
            $table->string('tax_id', 100)->nullable()->comment('الرقم الضريبي');
            $table->string('contact_person', 255)->nullable()->comment('شخص التواصل');
            $table->string('email', 255)->nullable()->comment('البريد الإلكتروني');
            $table->string('phone', 50)->nullable()->comment('الهاتف');
            $table->string('phone_secondary', 50)->nullable()->comment('هاتف ثانوي');
            $table->text('address')->nullable()->comment('العنوان');
            $table->string('city', 100)->nullable()->comment('المدينة');
            $table->string('country', 100)->nullable()->comment('البلد');
            $table->string('website', 255)->nullable()->comment('الموقع الإلكتروني');
            $table->string('logo_path', 500)->nullable()->comment('مسار الشعار');
            $table->string('payment_terms', 100)->nullable()->comment('شروط الدفع');
            $table->decimal('credit_limit', 15, 3)->default(0)->comment('حد الائتمان');
            $table->decimal('current_balance', 15, 3)->default(0)->comment('الرصيد الحالي');
            $table->decimal('total_purchases', 15, 3)->default(0)->comment('إجمالي المشتريات');
            $table->integer('rating')->default(0)->comment('التقييم');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->boolean('is_active')->default(true)->comment('نشط');
            $table->uuid('created_by')->nullable()->comment('منشئ');
            $table->uuid('updated_by')->nullable()->comment('محدث');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_suppliers_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('created_by', 'fk_suppliers_created_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
            $table->foreign('updated_by', 'fk_suppliers_updated_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Unique Constraints
            $table->unique(['company_id', 'supplier_code'], 'uniq_suppliers_code_company');

            // Indexes
            $table->index('company_id', 'idx_suppliers_company_id');
            $table->index('is_active', 'idx_suppliers_is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};