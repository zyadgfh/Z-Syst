<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prescription_id')->comment('معرف الوصفة');
            $table->uuid('product_id')->comment('معرف المنتج');
            $table->string('drug_name', 255)->nullable()->comment('اسم الدواء');
            $table->string('dosage', 100)->nullable()->comment('الجرعة');
            $table->string('frequency', 100)->nullable()->comment('التواتر');
            $table->string('duration', 100)->nullable()->comment('المدة');
            $table->decimal('quantity_prescribed', 15, 3)->default(0)->comment('الكمية الموصوفة');
            $table->decimal('quantity_dispensed', 15, 3)->default(0)->comment('الكمية المصفاة');
            $table->text('instructions')->nullable()->comment('تعليمات');
            $table->boolean('substitution_allowed')->default(true)->comment('الاستبدال مسموح');
            $table->string('status', 50)->default('pending')->comment('الحالة');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('prescription_id', 'fk_prescription_items_prescription_id')
                  ->references('id')->on('prescriptions')
                  ->onDelete('cascade');
            $table->foreign('product_id', 'fk_prescription_items_product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');

            // Indexes
            $table->index('prescription_id', 'idx_prescription_items_prescription_id');
            $table->index('product_id', 'idx_prescription_items_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
    }
};