<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable()->comment('معرف الشركة');
            $table->uuid('branch_id')->nullable()->comment('معرف الفرع');
            $table->string('key', 255)->comment('المفتاح');
            $table->text('value')->nullable()->comment('القيمة');
            $table->string('type', 50)->default('string')->comment('النوع');
            $table->string('group', 100)->nullable()->comment('المجموعة');
            $table->text('description')->nullable()->comment('الوصف');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('company_id', 'fk_settings_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('branch_id', 'fk_settings_branch_id')
                  ->references('id')->on('branches')
                  ->onDelete('cascade');

            // Unique Constraints
            $table->unique(['company_id', 'branch_id', 'key'], 'uniq_settings_scope_key');

            // Indexes
            $table->index('company_id', 'idx_settings_company_id');
            $table->index('branch_id', 'idx_settings_branch_id');
            $table->index('key', 'idx_settings_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};