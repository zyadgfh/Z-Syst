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
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('product_stock_id')->nullable()->constrained()->onDelete('restrict');
            
            $table->decimal('quantity_requested', 10, 2);
            $table->decimal('quantity_sent', 10, 2)->default(0);
            $table->decimal('quantity_received', 10, 2)->default(0);
            
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['stock_transfer_id', 'product_id']);
            $table->index('product_id');
            $table->index('batch_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
    }
};
