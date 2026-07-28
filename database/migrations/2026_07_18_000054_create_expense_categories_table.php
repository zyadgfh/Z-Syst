<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('expense_categories')) {
            return;
        }
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->string('name', 255)->comment('اسم الفئة');
            $table->text('description')->nullable()->comment('الوصف');
            $table->boolean('is_active')->default(true)->comment('نشط');
            $table->integer('sort_order')->default(0)->comment('الترتيب');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_expense_categories_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');

            // Indexes
            $table->index('company_id', 'idx_expense_categories_company_id');
            $table->index('is_active', 'idx_expense_categories_is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};