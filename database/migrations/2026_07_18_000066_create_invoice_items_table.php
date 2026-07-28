<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoice_items')) {
            return;
        }
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id')->comment('معرف الفاتورة');
            $table->text('description')->nullable()->comment('الوصف');
            $table->integer('quantity')->default(1)->comment('الكمية');
            $table->decimal('unit_price', 12, 3)->default(0)->comment('سعر الوحدة');
            $table->decimal('total', 15, 3)->default(0)->comment('الإجمالي');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('invoice_id', 'fk_invoice_items_invoice_id')
                  ->references('id')->on('invoices')
                  ->onDelete('cascade');

            // Indexes
            $table->index('invoice_id', 'idx_invoice_items_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};