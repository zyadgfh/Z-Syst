<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->string('account_code', 50)->comment('كود الحساب');
            $table->string('name', 255)->comment('اسم الحساب');
            $table->string('type', 50)->comment('نوع الحساب');
            $table->uuid('parent_id')->nullable()->comment('حساب أب');
            $table->decimal('balance', 15, 3)->default(0)->comment('الرصيد');
            $table->boolean('is_active')->default(true)->comment('نشط');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_accounts_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('parent_id', 'fk_accounts_parent_id')
                  ->references('id')->on('accounts')
                  ->onDelete('set null');

            // Unique Constraints
            $table->unique(['company_id', 'account_code'], 'uniq_accounts_code_company');

            // Indexes
            $table->index('company_id', 'idx_accounts_company_id');
            $table->index('type', 'idx_accounts_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};