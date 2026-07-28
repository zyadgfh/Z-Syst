<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_logs')) {
            return;
        }
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable()->comment('معرف الشركة');
            $table->string('log_name', 100)->nullable()->comment('اسم السجل');
            $table->text('description')->nullable()->comment('الوصف');
            $table->string('subject_type', 255)->nullable()->comment('نوع الموضوع');
            $table->uuid('subject_id')->nullable()->comment('معرف الموضوع');
            $table->string('causer_type', 255)->nullable()->comment('نوع المُسبب');
            $table->uuid('causer_id')->nullable()->comment('معرف المُسبب');
            $table->json('properties')->nullable()->comment('الخصائص');
            $table->string('event', 100)->nullable()->comment('الحدث');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('company_id', 'fk_activity_logs_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');

            // Indexes
            $table->index('company_id', 'idx_activity_logs_company_id');
            $table->index('log_name', 'idx_activity_logs_log_name');
            $table->index('subject_type', 'idx_activity_logs_subject_type');
            $table->index('causer_type', 'idx_activity_logs_causer_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};