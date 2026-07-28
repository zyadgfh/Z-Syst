<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('audit_logs')) {
            return;
        }
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable()->comment('معرف الشركة');
            $table->uuid('user_id')->nullable()->comment('معرف المستخدم');
            $table->string('table_name', 100)->comment('اسم الجدول');
            $table->uuid('record_id')->comment('معرف السجل');
            $table->string('action', 50)->comment('الإجراء');
            $table->json('old_values')->nullable()->comment('القيم القديمة');
            $table->json('new_values')->nullable()->comment('القيم الجديدة');
            $table->text('url')->nullable()->comment('الرابط');
            $table->string('ip_address', 45)->nullable()->comment('IP العناوين');
            $table->text('user_agent')->nullable()->comment('Agent المستخدم');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('company_id', 'fk_audit_logs_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('user_id', 'fk_audit_logs_user_id')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Indexes
            $table->index('company_id', 'idx_audit_logs_company_id');
            $table->index('user_id', 'idx_audit_logs_user_id');
            $table->index('table_name', 'idx_audit_logs_table_name');
            $table->index('action', 'idx_audit_logs_action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};