<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable()->comment('معرف الشركة');
            $table->uuid('user_id')->nullable()->comment('معرف المستخدم');
            $table->string('ticket_number', 50)->unique()->comment('رقم التذكرة');
            $table->string('subject', 255)->comment('الموضوع');
            $table->text('message')->comment('الرسالة');
            $table->string('priority', 20)->default('medium')->comment('الأولوية');
            $table->string('status', 20)->default('open')->comment('الحالة');
            $table->uuid('assigned_to')->nullable()->comment('مُعيَّن لـ');
            $table->timestamp('resolved_at')->nullable()->comment('تاريخ الحل');
            $table->timestamp('closed_at')->nullable()->comment('تاريخ الإغلاق');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_support_tickets_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('user_id', 'fk_support_tickets_user_id')
                  ->references('id')->on('users')
                  ->onDelete('set null');
            $table->foreign('assigned_to', 'fk_support_tickets_assigned_to')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Indexes
            $table->index('company_id', 'idx_support_tickets_company_id');
            $table->index('user_id', 'idx_support_tickets_user_id');
            $table->index('status', 'idx_support_tickets_status');
            $table->index('priority', 'idx_support_tickets_priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};