<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseHealthCheck extends Command
{
    protected $signature = 'db:health {--fix : Attempt to fix issues automatically}';

    protected $description = 'Validate database indexes, FK constraints, soft deletes, and overall health';

    public function handle(): int
    {
        $this->info('🏥 Database Health Check');
        $this->info('========================');
        $this->newLine();

        $issues = 0;
        $issues += $this->checkTableStats();
        $issues += $this->checkMissingIndexes();
        $issues += $this->checkSoftDeletes();
        $issues += $this->checkTimestamps();
        $issues += $this->checkOrphanedForeignKeys();
        $issues += $this->checkUniqueConstraints();

        $this->newLine();
        if ($issues === 0) {
            $this->info('✅ Database is healthy — no issues found.');
            return Command::SUCCESS;
        }

        $this->warn("⚠️  Found {$issues} issue(s). Run with --fix to attempt automatic repairs.");

        return Command::FAILURE;
    }

    protected function checkTableStats(): int
    {
        $this->info('📊 Table Statistics');
        $this->line(str_repeat('-', 60));

        $tables = $this->getTables();
        $total = count($tables);

        $this->line("  Total tables: {$total}");

        $withData = 0;
        $empty = 0;
        foreach ($tables as $name) {
            $count = DB::table($name)->count();
            if ($count > 0) {
                $withData++;
            } else {
                $empty++;
            }
        }

        $this->line("  With data: {$withData}");
        $this->line("  Empty: {$empty}");
        $this->newLine();

        return 0;
    }

    protected function checkMissingIndexes(): int
    {
        $this->info('🔍 Missing Indexes Check');
        $this->line(str_repeat('-', 60));

        $issues = 0;
        $tables = $this->getTables();

        // FK columns that should have indexes
        $criticalColumns = [
            'business_id', 'user_id', 'party_id', 'product_id',
            'purchase_id', 'sale_id', 'supplier_id', 'category_id',
            'account_id', 'branch_id', 'warehouse_id', 'grn_id',
        ];

        foreach ($tables as $name) {
            if (! Schema::hasTable($name)) {
                continue;
            }

            $indexes = $this->getTableIndexes($name);
            $columns = Schema::getColumnListing($name);

            foreach ($criticalColumns as $col) {
                if (in_array($col, $columns) && ! $this->hasIndexForColumn($indexes, $col)) {
                    $this->line("  ⚠️  {$name}.{$col} — missing index");
                    $issues++;

                    if ($this->option('fix')) {
                        Schema::table($name, fn ($t) => $t->index($col));
                        $this->line("     ✅ Fixed — index added");
                    }
                }
            }
        }

        if ($issues === 0) {
            $this->line('  ✅ All critical FK columns are indexed.');
        }

        $this->newLine();
        return $issues;
    }

    protected function checkSoftDeletes(): int
    {
        $this->info('🗑️  Soft Deletes Check');
        $this->line(str_repeat('-', 60));

        // Critical tables that SHOULD have soft deletes
        $shouldHaveSoftDeletes = [
            'products', 'sales', 'purchases', 'parties', 'expenses',
            'categories', 'subscriptions', 'invoices', 'stocks',
            'users', 'suppliers', 'warehouses', 'prescriptions',
        ];

        $issues = 0;
        foreach ($shouldHaveSoftDeletes as $name) {
            if (! Schema::hasTable($name)) {
                continue;
            }

            $hasSoftDeletes = Schema::hasColumn($name, 'deleted_at');

            if (! $hasSoftDeletes) {
                $this->line("  ⚠️  {$name} — missing soft deletes (hard delete only)");
                $issues++;

                if ($this->option('fix')) {
                    Schema::table($name, fn ($t) => $t->softDeletes());
                    $this->line("     ✅ Fixed — deleted_at column added");
                }
            }
        }

        if ($issues === 0) {
            $this->line('  ✅ All critical tables have soft deletes.');
        }

        $this->newLine();
        return $issues;
    }

    protected function checkTimestamps(): int
    {
        $this->info('⏰ Timestamps Check');
        $this->line(str_repeat('-', 60));

        $issues = 0;
        $tables = $this->getTables();

        // Laravel internal tables that legitimately skip timestamps
        $skip = [
            'failed_jobs', 'jobs', 'migrations',
            'model_has_permissions', 'model_has_roles', 'role_has_permissions',
            'password_reset_tokens', 'password_resets', 'personal_access_tokens',
        ];

        foreach ($tables as $name) {
            if (in_array($name, $skip)) {
                continue;
            }

            $hasCreated = Schema::hasColumn($name, 'created_at');
            $hasUpdated = Schema::hasColumn($name, 'updated_at');

            if (! $hasCreated || ! $hasUpdated) {
                $this->line("  ⚠️  {$name} — missing timestamps (created_at:" . ($hasCreated ? '✓' : '✗') . ', updated_at:' . ($hasUpdated ? '✓' : '✗') . ')');
                $issues++;

                if ($this->option('fix')) {
                    Schema::table($name, fn ($t) => $t->timestamps());
                    $this->line("     ✅ Fixed — timestamps added");
                }
            }
        }

        if ($issues === 0) {
            $this->line('  ✅ All tables have timestamps.');
        }

        $this->newLine();
        return $issues;
    }

    protected function checkOrphanedForeignKeys(): int
    {
        $this->info('🔗 Orphaned FK Check');
        $this->line(str_repeat('-', 60));

        $issues = 0;
        $tables = $this->getTables();

        foreach ($tables as $name) {
            if (! Schema::hasTable($name)) {
                continue;
            }

            $indexes = $this->getTableIndexes($name);
            $columns = Schema::getColumnListing($name);

            foreach ($columns as $col) {
                // Skip non-FK columns
                if (! str_ends_with($col, '_id') || $col === 'id') {
                    continue;
                }

                // Check if FK constraint exists (not just an index)
                $hasFk = false;
                foreach ($indexes as $idx) {
                    if (str_contains($idx->name ?? '', "fk_{$name}_{$col}") ||
                        str_contains($idx->name ?? '', "{$name}_{$col}_foreign")) {
                        $hasFk = true;
                        break;
                    }
                }

                // Note: SQLite doesn't always report FKs via PRAGMA index_list
                // So we check the table creation SQL
                $createSql = $this->getTableCreateSql($name);
                if (str_contains($createSql, "foreign key(\"{$col}\"")) {
                    $hasFk = true;
                }

                if (! $hasFk) {
                    $this->line("  ⚠️  {$name}.{$col} — no FK constraint (index only)");
                    $issues++;
                }
            }
        }

        if ($issues === 0) {
            $this->line('  ✅ All FK columns have constraints.');
        }

        $this->newLine();
        return $issues;
    }

    protected function checkUniqueConstraints(): int
    {
        $this->info('🔒 Unique Constraints Check');
        $this->line(str_repeat('-', 60));

        $issues = 0;

        // Columns that should be unique
        $shouldBeUnique = [
            'users' => ['email'],
            'currencies' => ['code'],
            'permissions' => ['name'],
            'roles' => ['name'],
        ];

        foreach ($shouldBeUnique as $table => $cols) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $indexes = $this->getTableIndexes($table);

            foreach ($cols as $col) {
                $hasUnique = false;
                foreach ($indexes as $idx) {
                    if (($idx->unique ?? false) && str_contains($idx->name ?? '', $col)) {
                        $hasUnique = true;
                        break;
                    }
                }

                if (! $hasUnique) {
                    $this->line("  ⚠️  {$table}.{$col} — should be unique");
                    $issues++;

                    if ($this->option('fix')) {
                        Schema::table($table, fn ($t) => $t->unique($col));
                        $this->line("     ✅ Fixed — unique constraint added");
                    }
                }
            }
        }

        if ($issues === 0) {
            $this->line('  ✅ All critical unique constraints are in place.');
        }

        $this->newLine();
        return $issues;
    }

    // ─── Helpers ───────────────────────────────────────────

    protected function getTables(): array
    {
        return collect(DB::select(
            "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name != 'migrations' ORDER BY name"
        ))->pluck('name')->toArray();
    }

    protected function getTableIndexes(string $table): array
    {
        return DB::select("PRAGMA index_list('{$table}')");
    }

    protected function hasIndexForColumn(array $indexes, string $column): bool
    {
        foreach ($indexes as $idx) {
            $info = DB::select("PRAGMA index_info('{$idx->name}')");
            foreach ($info as $col) {
                if ($col->name === $column) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function getTableCreateSql(string $table): string
    {
        $result = DB::select("SELECT sql FROM sqlite_master WHERE name = ?", [$table]);

        return $result[0]->sql ?? '';
    }
}
