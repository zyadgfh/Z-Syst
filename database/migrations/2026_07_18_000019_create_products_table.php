<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            return;
        }
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->uuid('category_id')->nullable()->comment('معرف الفئة');
            $table->uuid('manufacturer_id')->nullable()->comment('معرف المصنع');
            $table->string('name', 255)->comment('اسم المنتج');
            $table->string('generic_name', 255)->nullable()->comment('الاسم العلمي');
            $table->string('brand_name', 255)->nullable()->comment('الاسم التجاري');
            $table->string('barcode', 100)->nullable()->comment('الباركود');
            $table->string('sku', 100)->nullable()->comment('رمز المنتج');
            $table->text('description')->nullable()->comment('الوصف');
            $table->string('dosage_form', 50)->nullable()->comment('شكل الجرعة');
            $table->string('strength', 100)->nullable()->comment('القوة');
            $table->string('unit_of_measure', 50)->default('piece')->comment('وحدة القياس');
            $table->integer('pack_size')->default(1)->comment('حجم العلبة');
            $table->string('pack_unit', 50)->nullable()->comment('وحدة التعبئة');
            $table->string('atc_code', 50)->nullable()->comment('رمز ATC');
            $table->boolean('requires_prescription')->default(false)->comment('يحتاج وصفة');
            $table->boolean('is_controlled')->default(false)->comment('مادة محكمة');
            $table->string('controlled_schedule', 20)->nullable()->comment('جدول التحكم');
            $table->string('storage_conditions', 100)->nullable()->comment('ظروف التخزين');
            $table->integer('min_stock_level')->default(0)->comment('الحد الأدنى');
            $table->integer('reorder_point')->default(0)->comment('نقطة إعادة الطلب');
            $table->integer('max_stock_level')->default(0)->comment('الحد الأقصى');
            $table->decimal('cost_price', 12, 3)->default(0)->comment('سعر التكلفة');
            $table->decimal('selling_price', 12, 3)->default(0)->comment('سعر البيع');
            $table->decimal('wholesale_price', 12, 3)->default(0)->comment('سعر الجملة');
            $table->uuid('tax_rate_id')->nullable()->comment('معرف نسبة الضريبة');
            $table->decimal('discount_percentage', 5, 2)->default(0)->comment('نسبة الخصم');
            $table->boolean('is_active')->default(true)->comment('نشط');
            $table->string('image_path', 500)->nullable()->comment('مسار الصورة');
            $table->json('metadata')->default('{}')->comment('بيانات إضافية');
            $table->uuid('created_by')->nullable()->comment('منشئ');
            $table->uuid('updated_by')->nullable()->comment('محدث');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_products_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('category_id', 'fk_products_category_id')
                  ->references('id')->on('categories')
                  ->onDelete('set null');
            $table->foreign('manufacturer_id', 'fk_products_manufacturer_id')
                  ->references('id')->on('manufacturers')
                  ->onDelete('set null');
            $table->foreign('tax_rate_id', 'fk_products_tax_rate_id')
                  ->references('id')->on('tax_rates')
                  ->onDelete('set null');
            $table->foreign('created_by', 'fk_products_created_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
            $table->foreign('updated_by', 'fk_products_updated_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Unique Constraints
            $table->unique(['company_id', 'barcode'], 'uniq_products_barcode_company');
            $table->unique(['company_id', 'sku'], 'uniq_products_sku_company');

            // Indexes
            $table->index('company_id', 'idx_products_company_id');
            $table->index('category_id', 'idx_products_category_id');
            $table->index('manufacturer_id', 'idx_products_manufacturer_id');
            $table->index('name', 'idx_products_name');
            $table->index('generic_name', 'idx_products_generic_name');
            $table->index('requires_prescription', 'idx_products_requires_prescription');
            $table->index('is_controlled', 'idx_products_is_controlled');
            $table->index('is_active', 'idx_products_is_active');
        });

        // Full-text Search Index (PostgreSQL-specific; skipped on SQLite)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                CREATE INDEX IF NOT EXISTS idx_products_search ON products
                USING GIN (to_tsvector('arabic', COALESCE(name, '') || ' ' || COALESCE(generic_name, '') || ' ' || COALESCE(brand_name, '')))
            ");
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_products_search');
        Schema::dropIfExists('products');
    }
};
