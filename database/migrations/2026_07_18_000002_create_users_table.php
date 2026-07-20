<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('company_id')->comment('معرف الشركة');
                $table->uuid('branch_id')->nullable()->comment('معرف الفرع');
                $table->uuid('department_id')->nullable()->comment('معرف القسم');
                $table->string('first_name', 100)->comment('الاسم الأول');
                $table->string('last_name', 100)->comment('الاسم الأخير');
                $table->string('email', 255)->unique()->comment('البريد الإلكتروني');
                $table->string('phone', 50)->nullable()->comment('الهاتف');
                $table->string('password', 255)->comment('كلمة المرور');
                $table->string('avatar_path', 500)->nullable()->comment('مسار الصورة الشخصية');
                $table->string('role', 50)->comment('الدور: super_admin, company_admin, branch_manager, pharmacist, cashier');
                $table->boolean('is_active')->default(true)->comment('نشط');
                $table->timestamp('email_verified_at')->nullable()->comment('توثيق البريد');
                $table->text('two_factor_secret')->nullable()->comment('المفتاح الثنائي');
                $table->text('two_factor_recovery_codes')->nullable()->comment('رموز الاستعادة');
                $table->timestamp('last_login_at')->nullable()->comment('آخر دخول');
                $table->string('last_login_ip', 45)->nullable()->comment('IP آخر دخول');
                $table->string('locale', 10)->default('ar')->comment('اللغة');
                $table->string('timezone', 50)->default('Africa/Cairo')->comment('المنطقة الزمنية');
                $table->timestamps();
                $table->softDeletes();

                // Foreign Keys
                $table->foreign('company_id', 'fk_users_company_id')
                      ->references('id')->on('companies')
                      ->onDelete('cascade');
                $table->foreign('branch_id', 'fk_users_branch_id')
                      ->references('id')->on('branches')
                      ->onDelete('set null');
                $table->foreign('department_id', 'fk_users_department_id')
                      ->references('id')->on('departments')
                      ->onDelete('set null');

                // Indexes
                $table->index('email', 'idx_users_email');
                $table->index('company_id', 'idx_users_company_id');
                $table->index('branch_id', 'idx_users_branch_id');
                $table->index('department_id', 'idx_users_department_id');
                $table->index('role', 'idx_users_role');
                $table->index('is_active', 'idx_users_is_active');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};