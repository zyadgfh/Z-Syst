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
        Schema::create('fefo_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->boolean('fefo_enabled')->default(true);
            $table->enum('deduction_mode', ['automatic', 'manual_suggestion'])->default('automatic');
            $table->integer('expiry_grace_days')->default(30)->comment('Days before expiry to flag as warning');
            $table->boolean('auto_deduct_expired_stock')->default(false);
            $table->boolean('notify_on_fefo_deduction')->default(true);
            $table->integer('min_stock_for_fefo')->default(0)->comment('Minimum stock before auto FEFO kicks in');
            $table->timestamps();

            $table->unique('business_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fefo_settings');
    }
};

