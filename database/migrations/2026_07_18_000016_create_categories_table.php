<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->uuid('parent_id')->nullable()->comment('التصنيف الأب');
            $table->string('name', 255)->comment('اسم التصنيف');
            $table->string('slug', 255)->comment('slug التصنيف');
            $table->text('description')->nullable()->comment('الوصف');
            $table->string('image_path', 500)->nullable()->comment('مسار الصورة');
            $table->integer('sort_order')->default(0)->comment('الترتيب');
            $table->integer('level')->default(0)->comment('مستوى التسلسل');
            $table->boolean('is_active')->default(true)->comment('نشط');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_categories_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('parent_id', 'fk_categories_parent_id')
                  ->references('id')->on('categories')
                  ->onDelete('set null');

            // Unique Constraints
            $table->unique(['company_id', 'slug'], 'uniq_categories_slug_company');

            // Indexes
            $table->index('parent_id', 'idx_categories_parent_id');
            $table->index('company_id', 'idx_categories_company_id');
            $table->index('is_active', 'idx_categories_is_active');
            $table->index('level', 'idx_categories_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};