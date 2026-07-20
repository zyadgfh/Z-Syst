<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('model_has_roles')) {
            return;
        }

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->string('model_type', 255);
            $table->uuid('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);

            $table->index('model_id', 'idx_model_has_roles_model_id');
            $table->index('model_type', 'idx_model_has_roles_model_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_has_roles');
    }
};
