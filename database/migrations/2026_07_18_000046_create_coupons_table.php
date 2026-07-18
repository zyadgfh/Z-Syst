<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->string('code', 100)->comment('كود الكوبون');
            $table->string('name', 255)->comment('اسم الكوبون');
            $table->string('type', 20)->comment('نوع الكوبون');
            $table->decimal('value', 10, 3)->default(0)->comment('قيمة الكوبون');
            $table->decimal('min_order_amount', 15, 3)->default(0)->comment('الحد الأدنى للطلب');
            $table->decimal('max_discount', 15, 3)->nullable()->comment('أقصى خصم');
            $table->integer('usage_limit')->nullable()->comment('حد الاستخدام');
            $table->integer('used_count')->default(0)->comment('عدد الاستخدام');
            $table->integer('per_user_limit')->default(1)->comment('حد الاستخدام لكل مستخدم');
            $table->timestamp('valid_from')->nullable()->comment('صالح من');
            $table->timestamp('valid_until')->nullable()->comment('صالح حتى');
            $table->boolean('is_active')->default(true)->comment('نشط');
            $table->jsonb('applicable_products')->nullable()->comment('المنتجات المطبقة');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_coupons_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');

            // Unique Constraints
            $table->unique(['company_id', 'code'], 'uniq_coupons_code_company');

            // Indexes
            $table->index('company_id', 'idx_coupons_company_id');
            $table->index('is_active', 'idx_coupons_is_active');
            $table->index('valid_until', 'idx_coupons_valid_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};