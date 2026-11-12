<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the columns that models mass-assign and production already has, but that no
 * migration in this repository ever declared.
 *
 * These were the last of the schema drift: because production was built by a
 * different path than `php artisan migrate`, twelve columns existed live while
 * remaining absent from the migration set. The consequence was not a production
 * error (the columns were there) but an unreproducible schema — `migrate:fresh`
 * built a database *missing* them, so every fresh environment (CI sqlite, local
 * dev, a future staging box) diverged from production and the models that touch
 * these fields broke there. `tests/Feature/SchemaDriftTest.php` now fails the build
 * if this class of gap reappears.
 *
 * Types, nullability, defaults, foreign keys and indexes mirror production exactly
 * (read from information_schema / pg_catalog on 2026-11-10). Every change is
 * guarded by Schema::hasColumn(), so on production — where these columns already
 * exist — this migration is a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        // audit_logs.actor_user_id: who performed the audited action, distinct from
        // the pre-existing user_id column.
        if (! Schema::hasColumn('audit_logs', 'actor_user_id')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->uuid('actor_user_id')->nullable();
                $table->foreign('actor_user_id')
                    ->references('id')->on('app_users')->onDelete('SET NULL');
                $table->index(['actor_user_id', 'created_at'], 'audit_logs_actor_created_idx');
            });
        }

        // branches: its manager, the default-branch flag, and free-form metadata.
        if (! Schema::hasColumn('branches', 'manager_user_id')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->uuid('manager_user_id')->nullable();
                $table->foreign('manager_user_id')
                    ->references('id')->on('app_users')->onDelete('SET NULL');
                $table->index('manager_user_id', 'idx_branches_fk_48c86c64c941cc9c22d7a992158662f1');
            });
        }
        if (! Schema::hasColumn('branches', 'is_default')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->boolean('is_default')->default(false);
            });
        }
        if (! Schema::hasColumn('branches', 'metadata')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->json('metadata')->nullable();
            });
        }

        // sales.idempotency_key: client-supplied key that makes a sale retry-safe.
        // Production enforces it with a *partial* unique index (one key per business,
        // NULLs exempt) — see the pgsql block below.
        if (! Schema::hasColumn('sales', 'idempotency_key')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->text('idempotency_key')->nullable();
            });
        }

        // stock_transfers: same idempotency contract, plus completion timestamp.
        if (! Schema::hasColumn('stock_transfers', 'idempotency_key')) {
            Schema::table('stock_transfers', function (Blueprint $table) {
                $table->text('idempotency_key')->nullable();
            });
        }
        if (! Schema::hasColumn('stock_transfers', 'completed_at')) {
            Schema::table('stock_transfers', function (Blueprint $table) {
                $table->timestamp('completed_at')->nullable();
            });
        }

        // sale_return_details: the model keeps timestamps; production stores them
        // as nullable timestamptz with no default.
        if (! Schema::hasColumn('sale_return_details', 'created_at')) {
            Schema::table('sale_return_details', function (Blueprint $table) {
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        // stock_movements: which warehouse moved, at what unit cost, and by whom.
        if (! Schema::hasColumn('stock_movements', 'warehouse_id')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->unsignedBigInteger('warehouse_id')->nullable();
                $table->foreign('warehouse_id')
                    ->references('id')->on('warehouses')->onDelete('SET NULL');
                $table->index('warehouse_id', 'idx_stock_movements_fk_67f0c8eb3cbe0ca500a68116beb1233e');
            });
        }
        if (! Schema::hasColumn('stock_movements', 'unit_cost')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->decimal('unit_cost', 14, 4)->default(0);
            });
        }
        if (! Schema::hasColumn('stock_movements', 'created_by')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->uuid('created_by')->nullable();
                $table->foreign('created_by')
                    ->references('id')->on('app_users')->onDelete('SET NULL');
                $table->index('created_by', 'idx_stock_movements_fk_b4776171409ecfdfcd814df376737b0e');
            });
        }

        // Postgres-only: partial unique indexes have no portable Schema-builder
        // equivalent (sqlite/CI would reject the WHERE clause). They are what make a
        // duplicate idempotency key impossible per business while allowing the
        // overwhelmingly common NULL case.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS sales_business_idempotency_key_uidx ON sales (business_id, idempotency_key) WHERE idempotency_key IS NOT NULL');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS stock_transfers_business_idempotency_key_uidx ON stock_transfers (business_id, idempotency_key) WHERE idempotency_key IS NOT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS stock_transfers_business_idempotency_key_uidx');
            DB::statement('DROP INDEX IF EXISTS sales_business_idempotency_key_uidx');
        }

        foreach (['stock_movements' => ['warehouse_id', 'unit_cost', 'created_by']] as $table => $columns) {
            foreach ($columns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    Schema::table($table, function (Blueprint $blueprint) use ($column) {
                        $blueprint->dropColumn($column);
                    });
                }
            }
        }

        foreach (['sale_return_details' => ['created_at', 'updated_at']] as $table => $columns) {
            if (Schema::hasColumn($table, $columns[0])) {
                Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                    $blueprint->dropColumn($columns);
                });
            }
        }

        foreach ([['stock_transfers', 'completed_at'], ['stock_transfers', 'idempotency_key'], ['sales', 'idempotency_key']] as [$table, $column]) {
            if (Schema::hasColumn($table, $column)) {
                Schema::table($table, function (Blueprint $blueprint) use ($column) {
                    $blueprint->dropColumn($column);
                });
            }
        }

        foreach (['metadata', 'is_default', 'manager_user_id'] as $column) {
            if (Schema::hasColumn('branches', $column)) {
                Schema::table('branches', function (Blueprint $blueprint) use ($column) {
                    $blueprint->dropColumn($column);
                });
            }
        }

        if (Schema::hasColumn('audit_logs', 'actor_user_id')) {
            Schema::table('audit_logs', function (Blueprint $blueprint) {
                $blueprint->dropColumn('actor_user_id');
            });
        }
    }
};
