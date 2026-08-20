<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip CHECK constraints for SQLite (not supported via ALTER TABLE)
        // These would be applied in PostgreSQL/MySQL production environment
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE stocks ADD CONSTRAINT check_product_stock_non_negative CHECK (productStock >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE stocks DROP CONSTRAINT check_product_stock_non_negative');
        }
    }
};
