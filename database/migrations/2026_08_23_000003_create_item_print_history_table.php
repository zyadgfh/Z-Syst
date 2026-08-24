<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_print_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('barcode_number')->nullable();
            $table->integer('quantity')->default(1);
            $table->string('size')->default('standard');
            $table->json('options')->nullable(); // show_price, show_expiry, show_batch, show_code, etc.
            $table->string('batch_no')->nullable();
            $table->string('pdf_filename')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'product_id']);
            $table->index(['business_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_print_history');
    }
};
