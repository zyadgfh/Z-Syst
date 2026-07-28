<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('insurance_plans')) {
            return;
        }
        Schema::create('insurance_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('insurance_company_id')->comment('معرف شركة التأمين');
            $table->string('name', 255)->comment('اسم الخطة');
            $table->string('code', 100)->comment('كود الخطة');
            $table->decimal('coverage_percentage', 5, 2)->default(0)->comment('نسبة التغطية');
            $table->decimal('max_coverage', 15, 3)->default(0)->comment('أقصى تغطية');
            $table->decimal('deductible', 15, 3)->default(0)->comment('الاشتراكية');
            $table->decimal('copay_percentage', 5, 2)->default(0)->comment('نسبة التحمل');
            $table->json('covered_categories')->nullable()->comment('الفئات المغطاة');
            $table->json('excluded_categories')->nullable()->comment('الفئات المستبعدة');
            $table->boolean('is_active')->default(true)->comment('نشط');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('insurance_company_id', 'fk_insurance_plans_company_id')
                  ->references('id')->on('insurance_companies')
                  ->onDelete('cascade');

            // Unique Constraints
            $table->unique(['insurance_company_id', 'code'], 'uniq_insurance_plans_code');

            // Indexes
            $table->index('insurance_company_id', 'idx_insurance_plans_company_id');
            $table->index('is_active', 'idx_insurance_plans_is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_plans');
    }
};