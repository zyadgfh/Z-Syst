<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 255)->comment('نوع الإشعار');
            $table->string('notifiable_type', 255)->comment('نوع المُرسل له');
            $table->uuid('notifiable_id')->comment('معرف المُرسل له');
            $table->jsonb('data')->comment('البيانات');
            $table->timestamp('read_at')->nullable()->comment('تاريخ القراءة');
            $table->timestamps();

            // Indexes
            $table->index(['notifiable_type', 'notifiable_id'], 'idx_notifications_notifiable');
            $table->index('type', 'idx_notifications_type');
            $table->index('read_at', 'idx_notifications_read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};