<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('parties')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('supplier_item_code')->nullable();
            $table->string('supplier_barcode')->nullable();
            $table->double('supplier_purchase_price')->nullable();
            $table->string('supplier_currency')->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->integer('minimum_order_quantity')->nullable();
            $table->boolean('is_preferred')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'supplier_id', 'business_id'], 'unique_item_supplier');
            $table->index(['business_id', 'product_id']);
            $table->index(['business_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_suppliers');
    }
};
