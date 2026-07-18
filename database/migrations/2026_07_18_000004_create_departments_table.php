<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->uuid('branch_id')->comment('معرف الفرع');
            $table->string('name', 255)->comment('اسم القسم');
            $table->string('code', 50)->comment('الكود');
            $table->text('description')->nullable()->comment('الوصف');
            $table->uuid('manager_id')->nullable()->comment('مدير القسم');
            $table->boolean('is_active')->default(true)->comment('نشط');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_departments_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('branch_id', 'fk_departments_branch_id')
                  ->references('id')->on('branches')
                  ->onDelete('cascade');
            $table->foreign('manager_id', 'fk_departments_manager_id')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Unique Constraints
            $table->unique(['branch_id', 'code'], 'uniq_departments_code_branch');

            // Indexes
            $table->index('company_id', 'idx_departments_company_id');
            $table->index('branch_id', 'idx_departments_branch_id');
            $table->index('manager_id', 'idx_departments_manager_id');
            $table->index('is_active', 'idx_departments_is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};