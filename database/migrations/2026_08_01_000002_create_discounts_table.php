<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->string('name', 255)->comment('اسم الخصم');
            $table->string('code', 100)->nullable()->unique()->comment('كود الخصم الترويجي');
            $table->enum('type', ['percentage', 'fixed'])->default('percentage')->comment('نوع الخصم: نسبة أو قيمة ثابتة');
            $table->decimal('value', 12, 3)->default(0)->comment('قيمة الخصم');
            $table->decimal('min_purchase_amount', 12, 3)->nullable()->comment('الحد الأدنى للشراء');
            $table->decimal('max_discount_amount', 12, 3)->nullable()->comment('الحد الأقصى للخصم');
            $table->enum('apply_to', ['all', 'products', 'categories', 'specific_products'])->default('all')->comment('تطبيق على');
            $table->date('start_date')->nullable()->comment('تاريخ البدء');
            $table->date('end_date')->nullable()->comment('تاريخ الانتهاء');
            $table->time('start_time')->nullable()->comment('وقت البدء');
            $table->time('end_time')->nullable()->comment('وقت الانتهاء');
            $table->integer('usage_limit')->nullable()->comment('حد الاستخدام');
            $table->integer('used_count')->default(0)->comment('عدد مرات الاستخدام');
            $table->boolean('is_active')->default(true)->comment('نشط');
            $table->text('description')->nullable()->comment('وصف الخصم');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id', 'fk_discounts_company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by', 'fk_discounts_created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by', 'fk_discounts_updated_by')->references('id')->on('users')->onDelete('set null');

            $table->index('company_id', 'idx_discounts_company_id');
            $table->index('is_active', 'idx_discounts_is_active');
            $table->index(['start_date', 'end_date'], 'idx_discounts_dates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};

