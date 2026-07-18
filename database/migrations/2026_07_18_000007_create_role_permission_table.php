<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->primary(['role_id', 'permission_id']);

            $table->index('role_id', 'idx_role_permission_role_id');
            $table->index('permission_id', 'idx_role_permission_permission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permission');
    }
};