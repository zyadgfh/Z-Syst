<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name', 255)->comment('اسم الشركة/الصيدلية');
                $table->string('trade_name', 255)->nullable()->comment('الاسم التجاري');
                $table->string('legal_name', 255)->nullable()->comment('الاسم القانوني');
                $table->string('tax_number', 50)->unique()->comment('الرقم الضريبي');
                $table->string('pharmacy_license', 100)->nullable()->comment('رخصة الصيدلية');
                $table->date('license_expiry')->nullable()->comment('انتهاء الرخصة');
                $table->string('logo_path', 500)->nullable()->comment('مسار الشعار');
                $table->text('address')->nullable()->comment('العنوان');
                $table->string('city', 100)->nullable()->comment('المدينة');
                $table->string('country', 100)->default('EG')->comment('البلد');
                $table->string('phone', 50)->nullable()->comment('الهاتف');
                $table->string('email', 255)->nullable()->comment('البريد الإلكتروني');
                $table->string('website', 255)->nullable()->comment('الموقع الإلكتروني');
                $table->string('subscription_plan', 50)->default('starter')->comment('خطة الاشتراك');
                $table->string('subscription_status', 50)->default('active')->comment('حالة الاشتراك');
                $table->timestamp('subscription_ends')->nullable()->comment('انتهاء الاشتراك');
                $table->json('settings')->default('{}')->comment('الإعدادات');
                $table->boolean('is_active')->default(true)->comment('نشط');
                $table->timestamps();
                $table->softDeletes();

                // Indexes
                $table->index('tax_number', 'idx_companies_tax_number');
                $table->index('is_active', 'idx_companies_is_active');
                $table->index('subscription_status', 'idx_companies_subscription_status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
