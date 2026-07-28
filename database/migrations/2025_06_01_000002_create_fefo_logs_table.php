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
        Schema::create('fefo_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_detail_id')->nullable()->constrained('sale_details')->nullOnDelete();
            $table->string('batch_no')->nullable();
            $table->date('expire_date')->nullable();
            $table->integer('quantity_deducted')->default(0);
            $table->integer('quantity_remaining_after')->default(0);
            $table->string('action_type')->default('sale_deduction')->comment('sale_deduction, manual_adjustment, expired_removal');
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'product_id']);
            $table->index(['business_id', 'expire_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fefo_logs');
    }
};

