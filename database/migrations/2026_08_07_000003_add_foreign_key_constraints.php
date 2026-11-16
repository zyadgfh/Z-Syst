<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // The original create-table migrations already define every foreign key
        // previously listed here. Adding them again fails on MySQL/PostgreSQL.
        // Keep this migration as a no-op so existing migration history is valid.
    }

    public function down(): void
    {
        // The constraints belong to the create-table migrations, not this one.
    }
};
