<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparison_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable();
            $table->json('product_ids');
            $table->string('share_token', 32)->unique()->nullable();
            $table->integer('view_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparison_history');
    }
};
