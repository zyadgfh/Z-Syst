<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Detect existing index names (SQLite)
        $existingIndexNames = [];
        try {
            $indexes = \DB::select("PRAGMA index_list('branches')");
            foreach ($indexes as $idx) {
                $existingIndexNames[] = $idx->name ?? null;
            }
        } catch (\Exception $e) {
            // ignore
        }

        Schema::table('branches', function (Blueprint $table) use ($existingIndexNames) {
            if (! in_array('branches_company_id_index', $existingIndexNames, true)) {
                try { $table->index('company_id'); } catch (\Exception $e) {}
            }

            if (! in_array('branches_is_active_index', $existingIndexNames, true)) {
                try { $table->index('is_active'); } catch (\Exception $e) {}
            }

            if (! in_array('branches_is_main_index', $existingIndexNames, true)) {
                try { $table->index('is_main'); } catch (\Exception $e) {}
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['is_main']);
        });
    }
};
