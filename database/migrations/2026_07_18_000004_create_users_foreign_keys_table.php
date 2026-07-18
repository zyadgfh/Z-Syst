<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add foreign keys to users table after branches and departments are created
            $table->foreign('branch_id', 'fk_users_branch_id')
                  ->references('id')->on('branches')
                  ->onDelete('set null');
            $table->foreign('department_id', 'fk_users_department_id')
                  ->references('id')->on('departments')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('fk_users_branch_id');
            $table->dropForeign('fk_users_department_id');
        });
    }
};