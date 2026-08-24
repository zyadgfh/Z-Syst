<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['low_stock', 'out_of_stock', 'expiring_soon', 'expired', 'overstock']);
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->text('message');
            $table->integer('current_stock')->default(0);
            $table->integer('threshold')->nullable();
            $table->boolean('acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('suggested_reorder_date')->nullable();
            $table->integer('suggested_reorder_qty')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_alerts');
    }
};
