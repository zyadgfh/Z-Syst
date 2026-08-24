<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('purchase_without_tax', 12, 2)->nullable();
            $table->decimal('purchase_with_tax', 12, 2)->nullable();
            $table->decimal('sales_price', 12, 2)->nullable();
            $table->decimal('wholesale_price', 12, 2)->nullable();
            $table->decimal('minimum_selling_price', 12, 2)->nullable();
            $table->string('change_reason')->nullable(); // manual, purchase, import, bulk_update
            $table->json('metadata')->nullable(); // reference_id, reference_type, etc.
            $table->timestamps();

            $table->index(['business_id', 'product_id']);
            $table->index(['business_id', 'product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_price_history');
    }
};
