<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Close the tenant-ownership hole on `business_id`.
 *
 * `business_id` is this application's tenant key at two layers at once: the
 * Eloquent global scope in App\Traits\TenantScoped, and 124 Row Level Security
 * policies in the database of the form `business_id = private.current_business_id()`.
 * Both compare by equality, so a row whose `business_id` is NULL belongs to no
 * tenant and is readable by nobody — not a cross-tenant leak, but an orphan:
 * written successfully, then permanently invisible.
 *
 * Twenty-five tables allowed NULL. Five of them must keep allowing it, and are
 * deliberately absent from the list below:
 *  - app_users: a user being onboarded has no business yet, and TenantScoped
 *    skips scoping entirely for such a user;
 *  - audit_logs: an audit trail must survive the deletion of its business
 *    (its migration already says nullable()->nullOnDelete());
 *  - drug_interactions, inventory_turnover_reports, product_inventory_analysis
 *    and supplier_performance: their models override tenantIncludesGlobals(),
 *    which exists precisely so a NULL row is a curated/platform-wide record
 *    visible to every tenant.
 *
 * The remaining tables are listed in TENANT_TABLES. Their create_* migrations
 * already declared `business_id` NOT NULL with a foreign key, so this migration
 * does not change the intended schema — it repairs a production database that was
 * built by a different path and drifted from it. Every one of them is empty, so
 * there is nothing to backfill.
 *
 * This is not a cosmetic difference. Two features were outright broken by it:
 * ApprovalWorkflowService and CreditDebitService created approval_steps and
 * credit_debit_items rows without a business_id, because only the *parent*
 * document was given one. Against this schema that raises a NOT NULL violation;
 * against the drifted one it silently stored NULL, hiding every approval step and
 * every supplier ledger line from the tenant that created it. Both services and
 * both models are fixed alongside this migration.
 */
return new class extends Migration
{
    /**
     * Tables whose `business_id` must be NOT NULL and reference businesses(id).
     *
     * @var array<int, string>
     */
    private const TENANT_TABLES = [
        'approval_steps',
        'approval_templates',
        'approval_workflows',
        'credit_debit_items',
        'insurance_coverages',
        'plan_subscribes',
        'prediction_settings',
        'receipt_settings',
        'receipts',
        'sales_forecasts',
        'stock_audit_details',
        'stock_reconciliations',
        'supplier_contracts',
        'supplier_credits',
        'supplier_debits',
        'supplier_ratings',
        'suppliers',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            // SQLite (tests, migrate:fresh) gets NOT NULL and the foreign key
            // straight from the create_* migrations, and the catalogues queried
            // below are Postgres-specific. Nothing to repair there.
            return;
        }

        $orphans = [];

        foreach (self::TENANT_TABLES as $table) {
            if (! $this->columnExists($table)) {
                continue;
            }

            // Refuse to guess a tenant. If rows without one exist, they are
            // already invisible to everybody, and inventing an owner would move
            // data between businesses — the one thing a schema migration must
            // never do silently.
            $nullRows = (int) DB::table($table)->whereNull('business_id')->count();

            if ($nullRows > 0) {
                $orphans[] = "{$table} ({$nullRows} row(s))";

                continue;
            }

            $this->addForeignKey($table);

            DB::statement("ALTER TABLE {$table} ALTER COLUMN business_id SET NOT NULL");
        }

        if ($orphans !== []) {
            throw new RuntimeException(
                'Cannot enforce tenant ownership on business_id: these tables hold rows with no '
                .'business, and no migration may invent one. Attribute or delete them first, then '
                .'re-run: '.implode(', ', $orphans)
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::TENANT_TABLES as $table) {
            if (! $this->columnExists($table)) {
                continue;
            }

            DB::statement("ALTER TABLE {$table} ALTER COLUMN business_id DROP NOT NULL");

            $name = $this->foreignKeyName($table);

            if ($this->constraintExists($name)) {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$name}");
            }
        }
    }

    private function columnExists(string $table): bool
    {
        return (bool) DB::selectOne(
            'SELECT 1 FROM information_schema.columns
              WHERE table_schema = current_schema() AND table_name = ? AND column_name = ?',
            [$table, 'business_id']
        );
    }

    /**
     * Laravel's default name for foreignId('business_id')->constrained().
     */
    private function foreignKeyName(string $table): string
    {
        return $table.'_business_id_foreign';
    }

    private function constraintExists(string $name): bool
    {
        return (bool) DB::selectOne('SELECT 1 FROM pg_constraint WHERE conname = ?', [$name]);
    }

    private function addForeignKey(string $table): void
    {
        $name = $this->foreignKeyName($table);

        if ($this->constraintExists($name)) {
            return;
        }

        // Idempotent against a foreign key that already exists under a different
        // name, so re-running this against a database that was repaired by hand
        // does not add a second, redundant constraint.
        $alreadyFk = DB::selectOne(
            "SELECT 1 FROM information_schema.key_column_usage kcu
               JOIN information_schema.constraint_column_usage ccu
                 ON ccu.constraint_name = kcu.constraint_name
              WHERE kcu.table_schema = current_schema()
                AND kcu.table_name = ?
                AND kcu.column_name = 'business_id'
                AND ccu.table_name = 'businesses'",
            [$table]
        );

        if ($alreadyFk) {
            return;
        }

        DB::statement(
            "ALTER TABLE {$table} ADD CONSTRAINT {$name}
                 FOREIGN KEY (business_id) REFERENCES businesses (id) ON DELETE CASCADE"
        );
    }
};
