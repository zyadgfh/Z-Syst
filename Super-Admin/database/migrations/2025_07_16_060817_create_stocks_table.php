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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('batch_no')->nullable();
            $table->double('productStock')->default(0);
            $table->double('productPurchasePrice')->default(0);
            $table->double('profit_percent')->default(0);
            $table->double('productSalePrice')->default(0);
            $table->double('productWholeSalePrice')->default(0);
            $table->double('productDealerPrice')->default(0);
            $table->string('mfg_date')->nullable();
            $table->string('expire_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
