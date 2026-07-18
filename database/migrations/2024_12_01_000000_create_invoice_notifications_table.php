<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            $table->enum('channel', ['whatsapp', 'sms', 'email', 'print']);
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed', 'read'])->default('pending');
            
            $table->string('recipient')->nullable(); // رقم الهاتف أو البريد
            $table->string('message_id')->nullable(); // معرف الرسالة من المزود
            $table->text('message_content')->nullable(); // محتوى الرسالة
            
            $table->string('file_path')->nullable(); // مسار الملف المرفق
            $table->string('file_type')->nullable(); // image, pdf
            
            $table->json('metadata')->nullable(); // بيانات إضافية
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            
            $table->timestamps();
            
            $table->index(['sale_id', 'channel']);
            $table->index(['status', 'created_at']);
            $table->index('channel');
            $table->index('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_notifications');
    }
};