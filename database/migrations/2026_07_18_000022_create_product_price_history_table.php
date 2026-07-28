<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_price_history')) {
            return;
        }
        Schema::create('product_price_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id')->comment('معرف المنتج');
            $table->string('field_changed', 50)->comment('الحقل المتغير');
            $table->decimal('old_value', 12, 3)->comment('القيمة القديمة');
            $table->decimal('new_value', 12, 3)->comment('القيمة الجديدة');
            $table->uuid('changed_by')->nullable()->comment('من غيرها');
            $table->timestamp('changed_at')->useCurrent()->comment('تاريخ التغيير');
            $table->text('reason')->nullable()->comment('السبب');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('product_id', 'fk_product_price_history_product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');
            $table->foreign('changed_by', 'fk_product_price_history_changed_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Indexes
            $table->index('product_id', 'idx_product_price_history_product_id');
            $table->index('field_changed', 'idx_product_price_history_field_changed');
            $table->index('changed_at', 'idx_product_price_history_changed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_history');
    }
};