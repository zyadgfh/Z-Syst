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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('productName');
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('type_id')->nullable()->constrained('medicine_types')->cascadeOnDelete();
            $table->foreignId('manufacturer_id')->nullable()->constrained('manufacturers')->cascadeOnDelete();
            $table->foreignId('box_size_id')->nullable()->constrained()->cascadeOnDelete();
            $table->double('purchase_without_tax')->default(0);
            $table->double('purchase_with_tax')->default(0);
            $table->double('profit_percent')->default(0);
            $table->double('sales_price')->default(0);
            $table->integer('alert_qty')->default(0);
            $table->double('wholesale_price')->default(0);
            $table->string('productCode')->nullable();
            $table->longText('images')->nullable();
            $table->foreignId('tax_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('tax_type')->default('exclusive');
            $table->longText('meta')->nullable(); // strength, generic_name, shelf, medicine_details
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
