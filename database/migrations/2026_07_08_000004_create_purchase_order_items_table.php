<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_order_items')) {
            Schema::create('purchase_order_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('purchase_order_id')->index();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->unsignedBigInteger('drug_id')->nullable()->index();
                $table->integer('quantity')->default(1);
                $table->decimal('price', 12, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
