<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->uuid('product_id')->comment('معرف المنتج');
            $table->uuid('product_variant_id')->nullable()->comment('معرف النوع (إذا كان السعر لنوع معين)');
            $table->string('tier_name', 100)->comment('اسم الشريحة (retail, wholesale, bulk, insurance, contract)');
            $table->string('tier_label', 255)->comment('تسمية الشريحة (قطاعي, جملة, تجزئة, تأمين, عقد)');
            $table->decimal('price', 12, 3)->default(0)->comment('السعر');
            $table->decimal('min_quantity', 12, 3)->nullable()->comment('الحد الأدنى للكمية لتطبيق هذا السعر');
            $table->decimal('max_quantity', 12, 3)->nullable()->comment('الحد الأقصى للكمية');
            $table->uuid('customer_group_id')->nullable()->comment('مجموعة العملاء المحددة');
            $table->boolean('is_default')->default(false)->comment('السعر الافتراضي');
            $table->date('start_date')->nullable()->comment('تاريخ بدء الصلاحية');
            $table->date('end_date')->nullable()->comment('تاريخ انتهاء الصلاحية');
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id', 'fk_pp_company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('product_id', 'fk_pp_product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('product_variant_id', 'fk_pp_variant_id')->references('id')->on('product_variants')->onDelete('cascade');
            $table->foreign('created_by', 'fk_pp_created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by', 'fk_pp_updated_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['product_id', 'tier_name', 'product_variant_id'], 'uniq_pp_product_tier');
            $table->index('company_id', 'idx_pp_company_id');
            $table->index('tier_name', 'idx_pp_tier_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};

