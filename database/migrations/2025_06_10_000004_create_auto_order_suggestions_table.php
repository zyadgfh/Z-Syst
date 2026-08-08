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
        Schema::create('auto_order_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('preferred_supplier_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->decimal('predicted_demand', 12, 2)->default(0);
            $table->decimal('current_stock', 12, 2)->default(0);
            $table->decimal('pending_purchases', 12, 2)->default(0);
            $table->decimal('suggested_order_qty', 12, 2)->default(0);
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->string('priority')->default('medium'); // high, medium, low
            $table->string('status')->default('pending'); // pending, approved, converted, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('converted_purchase_id')->nullable()->constrained('purchases')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('reasoning')->nullable(); // AI reasoning for suggestion
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'priority']);
            $table->index(['business_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_order_suggestions');
    }
};
