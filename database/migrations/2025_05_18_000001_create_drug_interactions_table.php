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
        Schema::create('drug_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('drug_a_name'); // Generic name or product name
            $table->string('drug_b_name'); // Generic name or product name
            $table->enum('severity', ['contraindicated', 'severe', 'moderate', 'minor'])->default('moderate');
            $table->text('description'); // What the interaction is
            $table->text('mechanism')->nullable(); // How it occurs
            $table->text('recommendation')->nullable(); // What to do / clinical advice
            $table->string('source')->nullable(); // Reference source
            $table->string('category')->nullable(); // e.g. 'pharmacodynamic', 'pharmacokinetic', 'unknown'
            $table->json('meta')->nullable(); // Extra metadata
            $table->timestamps();

            // Prevent duplicate drug interaction entries
            $table->unique(['business_id', 'drug_a_name', 'drug_b_name'], 'unique_drug_interaction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drug_interactions');
    }
};
