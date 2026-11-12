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
        Schema::create('inventory_turnover_reports', function (Blueprint $table) {
            $table->id();
            // Nullable on purpose. InventoryTurnoverReport overrides
            // TenantScoped::tenantIncludesGlobals(), which makes a row with
            // business_id IS NULL a platform-wide rollup visible to every tenant.
            // Declaring this NOT NULL would make that documented contract
            // unreachable, and the unique report key below treats NULL as a
            // distinct owner. Production allows NULL here; the migration now
            // agrees with both the model and the live schema.
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('report_type', ['monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_cogs', 15, 2)->default(0); // تكلفة البضاعة المباعة
            $table->decimal('average_inventory_value', 15, 2)->default(0); // متوسط قيمة المخزون
            $table->decimal('inventory_turnover_ratio', 10, 2)->default(0); // نسبة دوران المخزون
            $table->decimal('days_inventory_outstanding', 10, 2)->default(0); // أيام المخزون المعلقة
            $table->integer('total_products_analyzed')->default(0);
            $table->integer('slow_moving_count')->default(0); // منتجات بطيئة الحركة
            $table->integer('dead_stock_count')->default(0); // منتجات راكدة
            $table->decimal('total_slow_moving_value', 15, 2)->default(0); // قيمة المخزون البطيء
            $table->decimal('total_dead_stock_value', 15, 2)->default(0); // قيمة المخزون الراكد
            $table->decimal('total_inventory_value', 15, 2)->default(0); // إجمالي قيمة المخزون
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'report_type', 'period_start', 'period_end'], 'unique_inventory_report');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_turnover_reports');
    }
};
