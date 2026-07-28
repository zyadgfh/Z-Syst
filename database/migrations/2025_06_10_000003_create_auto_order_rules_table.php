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
        Schema::create('auto_order_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->decimal('min_stock_level', 12, 2)->nullable(); // minimum stock before reorder
            $table->decimal('max_stock_level', 12, 2)->nullable(); // maximum stock to order up to
            $table->decimal('reorder_point', 12, 2)->nullable(); // calculated reorder point
            $table->decimal('safety_stock', 12, 2)->nullable(); // calculated safety stock
            $table->integer('lead_time_days')->nullable(); // supplier lead time (overrides default)
            $table->decimal('min_order_qty', 12, 2)->nullable(); // minimum order quantity
            $table->decimal('max_order_qty', 12, 2)->nullable(); // maximum order quantity
            $table->decimal('order_multiple', 12, 2)->nullable(); // order in multiples of (e.g., box of 12)
            $table->foreignId('preferred_supplier_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->boolean('auto_approve')->default(false); // auto-create purchase order
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'product_id']);
            $table->index(['business_id', 'enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_order_rules');
    }
};

