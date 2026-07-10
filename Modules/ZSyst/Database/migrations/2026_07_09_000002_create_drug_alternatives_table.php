<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drug_alternatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drug_id')->constrained('drugs')->cascadeOnDelete();
            $table->foreignId('alternative_drug_id')->constrained('drugs')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['drug_id', 'alternative_drug_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drug_alternatives');
    }
};
