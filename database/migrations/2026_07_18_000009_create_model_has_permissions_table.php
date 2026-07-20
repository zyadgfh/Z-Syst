<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('model_has_permissions')) {
            return;
        }

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->string('model_type', 255);
            $table->uuid('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);

            $table->index('model_id', 'idx_model_has_permissions_model_id');
            $table->index('model_type', 'idx_model_has_permissions_model_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_has_permissions');
    }
};
