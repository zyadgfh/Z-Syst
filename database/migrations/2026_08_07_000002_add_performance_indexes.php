<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Performance indexes are managed by the table-specific migrations.
        // This historical migration intentionally remains a no-op to avoid
        // duplicating indexes that already exist in those migrations.
    }

    public function down(): void
    {
        // No-op: do not remove indexes owned by other migrations.
    }
};
