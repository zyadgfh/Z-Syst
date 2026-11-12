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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('subscriptionName');
            $table->integer('duration')->default(0); // Duration in days
            $table->double('offerPrice', 10, 2)->nullable();
            $table->double('subscriptionPrice', 10, 2)->default(0);
            // integer, not boolean: the code compares this column against 1 in the
            // landing page pricing block, the admin plan list and the subscription API
            // (Plan::where('status', 1), Plan::whereStatus(1)). Against a boolean
            // column that is `boolean = integer`, which Postgres has no operator for.
            $table->integer('status')->default(1);
            // json: production stores jsonb and Plan casts 'features' to json.
            $table->json('features')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
