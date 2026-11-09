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
        Schema::create('sale_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->double('price', 10, 2)->default(0);
            $table->double('purchase_price', 10, 2)->default(0);
            $table->double('loss_profit', 10, 2)->default(0);
            $table->string('batch_no')->nullable();
            $table->date('expire_date')->nullable();
            $table->integer('quantities')->default(0);
            // dose / dose_code are added by 2026_11_07_100002_add_dose_tracking_to_sale_details
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_details');
    }
};
