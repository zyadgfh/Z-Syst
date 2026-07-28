<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('company_id')->comment('معرف الشركة');
                $table->string('name', 255)->comment('اسم الفرع');
                $table->string('code', 50)->comment('الكود');
                $table->string('type', 50)->default('pharmacy')->comment('النوع: pharmacy, warehouse');
                $table->text('address')->nullable()->comment('العنوان');
                $table->string('city', 100)->nullable()->comment('المدينة');
                $table->string('phone', 50)->nullable()->comment('الهاتف');
                $table->string('email', 255)->nullable()->comment('البريد الإلكتروني');
                $table->json('working_hours')->nullable()->comment('ساعات العمل');
                $table->string('timezone', 50)->default('Africa/Cairo')->comment('المنطقة الزمنية');
                $table->string('currency', 3)->default('EGP')->comment('العملة');
                $table->decimal('tax_rate', 5, 2)->default(0)->comment('معدل الضريبة');
                $table->json('receipt_template')->default('{}')->comment('قالب الإيصال');
                $table->boolean('is_active')->default(true)->comment('نشط');
                $table->uuid('created_by')->nullable()->comment('منشئ');
                $table->uuid('updated_by')->nullable()->comment('محدث');
                $table->timestamps();
                $table->softDeletes();

                // Foreign Keys
                $table->foreign('company_id', 'fk_branches_company_id')
                      ->references('id')->on('companies')
                      ->onDelete('cascade');

                // Unique Constraints
                $table->unique(['company_id', 'code'], 'uniq_branches_code_company');

                // Indexes
                $table->index('company_id', 'idx_branches_company_id');
                $table->index('is_active', 'idx_branches_is_active');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};