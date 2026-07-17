<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sync_sessions')) {
            Schema::create('sync_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('device_id');
                $table->string('status')->default('online');
                $table->timestamp('last_sync_at')->nullable();
                $table->text('payload')->nullable();
                $table->timestamps();

                $table->index(['device_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_sessions');
    }
};
