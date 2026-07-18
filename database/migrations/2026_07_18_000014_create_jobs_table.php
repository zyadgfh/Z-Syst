<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedBigInteger('available_at');
            $table->unsignedBigInteger('created_at');

            $table->index('queue', 'idx_jobs_queue');
            $table->index('available_at', 'idx_jobs_available_at');
            $table->index('created_at', 'idx_jobs_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};