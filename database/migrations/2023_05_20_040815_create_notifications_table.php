<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            // Text, not morphs(): the notifiable is AppUser (uuid primary key) and the
            // polymorphic pair has to hold any model's key, so a bigint id cannot work.
            // Production declares both columns as nullable text.
            $table->string('notifiable_type')->nullable();
            $table->string('notifiable_id')->nullable();
            $table->index(['notifiable_type', 'notifiable_id']);
            // json, not text: production stores jsonb and the model casts 'data' to json.
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};
