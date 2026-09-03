<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfer_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // 'created', 'completed', 'cancelled', 'failed'
            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity')->nullable();
            $table->integer('from_stock_before')->nullable();
            $table->integer('from_stock_after')->nullable();
            $table->integer('to_stock_before')->nullable();
            $table->integer('to_stock_after')->nullable();
            $table->text('notes')->nullable();
            $table->string('status'); // 'success', 'failed'
            $table->json('metadata')->nullable(); // extra context: error messages, etc.
            $table->timestamps();

            $table->index(['business_id', 'stock_transfer_id', 'action']);
            $table->index(['business_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_audits');
    }
};
