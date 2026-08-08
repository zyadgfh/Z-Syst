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
        Schema::create('product_inventory_analysis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_id')->nullable()->constrained('inventory_turnover_reports')->cascadeOnDelete();
            $table->decimal('total_quantity_sold', 15, 2)->default(0);
            $table->decimal('total_sales_value', 15, 2)->default(0);
            $table->decimal('total_cogs', 15, 2)->default(0); // تكلفة البضاعة المباعة للمنتج
            $table->decimal('average_stock_level', 15, 2)->default(0); // متوسط مستوى المخزون
            $table->decimal('turnover_ratio', 10, 2)->default(0); // نسبة دوران هذا المنتج
            $table->decimal('days_inventory_outstanding', 10, 2)->default(0); // أيام المخزون
            $table->enum('abc_category', ['A', 'B', 'C'])->nullable(); // تصنيف ABC
            $table->enum('movement_category', ['fast', 'medium', 'slow', 'dead'])->default('medium'); // تصنيف السرعة
            $table->decimal('current_stock_value', 15, 2)->default(0); // قيمة المخزون الحالي
            $table->decimal('current_stock_qty', 15, 2)->default(0); // كمية المخزون الحالي
            $table->decimal('stock_velocity', 10, 2)->default(0); // سرعة البيع (وحدات/يوم)
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'product_id', 'report_id'], 'unique_product_analysis');
            $table->index(['business_id', 'movement_category']);
            $table->index(['business_id', 'abc_category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_inventory_analysis');
    }
};
