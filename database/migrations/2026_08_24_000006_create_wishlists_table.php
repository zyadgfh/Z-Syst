<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 64)->nullable()->index();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // A user (or session) can only wishlist a product once
            $table->unique(['user_id', 'product_id']);
            $table->unique(['session_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
