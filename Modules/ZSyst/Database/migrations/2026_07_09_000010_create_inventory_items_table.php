<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_items')) {
            Schema::create('inventory_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('drug_id')->nullable()->constrained('drugs')->nullOnDelete();
                $table->string('batch_number')->nullable();
                $table->date('expiry_date')->nullable();
                $table->integer('quantity_on_hand')->default(0);
                $table->decimal('unit_cost', 12, 2)->nullable();
                $table->string('location')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
