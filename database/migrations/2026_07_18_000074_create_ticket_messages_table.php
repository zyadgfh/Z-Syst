<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ticket_messages')) {
            return;
        }
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id')->comment('معرف التذكرة');
            $table->uuid('user_id')->nullable()->comment('معرف المستخدم');
            $table->text('message')->comment('الرسالة');
            $table->json('attachments')->nullable()->comment('المرفقات');
            $table->boolean('is_internal')->default(false)->comment('داخلي');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('ticket_id', 'fk_ticket_messages_ticket_id')
                  ->references('id')->on('support_tickets')
                  ->onDelete('cascade');
            $table->foreign('user_id', 'fk_ticket_messages_user_id')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Indexes
            $table->index('ticket_id', 'idx_ticket_messages_ticket_id');
            $table->index('user_id', 'idx_ticket_messages_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
    }
};