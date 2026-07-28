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
        Schema::create('sales_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('forecast_date');
            $table->decimal('predicted_quantity', 12, 2)->default(0);
            $table->decimal('predicted_revenue', 14, 2)->default(0);
            $table->decimal('confidence_score', 5, 2)->nullable(); // 0-100
            $table->decimal('lower_bound', 12, 2)->nullable(); // pessimistic
            $table->decimal('upper_bound', 12, 2)->nullable(); // optimistic
            $table->string('method_used')->nullable(); // moving_average, etc
            $table->json('factors')->nullable(); // seasonality, trend data
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'product_id', 'forecast_date']);
            $table->index(['business_id', 'forecast_date']);
            $table->unique(['business_id', 'product_id', 'forecast_date'], 'unique_forecast');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_forecasts');
    }
};

