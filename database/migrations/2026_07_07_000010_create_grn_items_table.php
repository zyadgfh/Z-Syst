<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grn_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('goods_received_notes')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number')->nullable();
            $table->decimal('quantity_received', 10, 2);
            $table->date('expiry_date')->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->decimal('unit_cost', 12, 2);
            $table->string('rack_location')->nullable();
            $table->timestamps();

            $table->index('product_id');
            $table->index('batch_number');
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_items');
    }
};
