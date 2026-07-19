<?php

declare(strict_types=1);

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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->index(); // stock_refill, prescription_ready, low_stock, purchase_request, general
            $table->string('subject');
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['company_id', 'recipient_id', 'is_read']);
            $table->index(['company_id', 'sender_id']);
            $table->index(['type', 'is_read']);
        });

        // Pivot table for message replies
        Schema::create('message_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('messages')->cascadeOnDelete();
            $table->foreignId('reply_id')->constrained('messages')->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['parent_id', 'reply_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_replies');
        Schema::dropIfExists('messages');
    }
};