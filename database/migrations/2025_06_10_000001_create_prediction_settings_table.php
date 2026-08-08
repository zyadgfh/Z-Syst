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
        Schema::create('prediction_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->boolean('prediction_enabled')->default(true);
            $table->string('forecast_period')->default('daily'); // daily, weekly, monthly
            $table->integer('forecast_days')->default(30); // number of days to forecast
            $table->integer('historical_months')->default(6); // months of data to analyze
            $table->enum('prediction_method', ['moving_average', 'weighted_moving_average', 'exponential_smoothing', 'combined'])->default('combined');
            $table->boolean('seasonal_adjustment')->default(true);
            $table->decimal('safety_stock_multiplier', 5, 2)->default(1.5); // multiplier for safety stock
            $table->integer('lead_time_days')->default(7); // default lead time for orders
            $table->decimal('confidence_threshold', 5, 2)->default(0.7); // min confidence to auto-order
            $table->boolean('auto_order_enabled')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique('business_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prediction_settings');
    }
};
