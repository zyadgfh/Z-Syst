<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Foreign keys are defined by the table-specific migrations.
        // This historical migration is intentionally a no-op because applying
        // the same constraints again causes duplicate-constraint failures on
        // fresh SQLite/PostgreSQL databases.
    }

    public function down(): void
    {
        // No-op: do not remove constraints owned by other migrations.
    }
};
