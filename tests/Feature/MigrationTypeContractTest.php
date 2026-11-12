<?php

namespace Tests\Feature;

use App\Models\StockMovement;
use App\Services\ApprovalWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Type-contract guard.
 *
 * Why this exists
 * ---------------
 * A column-level audit compared every column the migrations declare against the
 * live Supabase schema (148 tables, 1757 columns). It found divergences that no
 * existing test could see, because CI builds sqlite *from the migrations* — so a
 * migration that declares the wrong type produces a green build and a broken
 * production database.
 *
 * The faults it found, all now fixed and pinned here:
 *
 *  - `personal_access_tokens.tokenable_id` was declared bigint by morphs(), while
 *    AppUser's primary key is a uuid. On a freshly migrated database no user could
 *    be issued an API token at all (22P02). Production already stored uuid.
 *  - `notifications.notifiable_id` had the same problem, and `notifications.data`
 *    was declared text where production stores jsonb.
 *  - `notifications.deleted_at` did not exist in production while the model uses
 *    SoftDeletes, so every read added `WHERE deleted_at IS NULL` and raised 42703.
 *  - `plans.status` was declared boolean but is compared against 1 by the landing
 *    page, the admin plan list and the subscription API: `boolean = integer` has
 *    no Postgres operator. Production stores integer.
 *  - `gateways.status` was declared string and `gateways.charge` boolean, while
 *    production stores boolean and numeric. `charge` is a money amount added to
 *    the payable total, not a flag.
 *  - `approval_templates`/`approval_workflows.created_by|updated_by` were bigint
 *    in production while both models declare belongsTo(User::class) against a uuid
 *    key, and ApprovalWorkflowService typed the actor parameter as `int`.
 *  - 18 attribution columns the services write were declared by migrations but
 *    absent from production. Because the models were generated from the live
 *    schema, Eloquent's fill() discarded the keys silently — no error, just a
 *    permanent loss of who created the purchase order, which branch received the
 *    goods, who opened the stock audit.
 *
 * These tests need no secrets and no network access.
 */
class MigrationTypeContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Columns the application writes or queries, which therefore have to exist on
     * a freshly migrated database.
     *
     * @return array<string, array<int, string>>
     */
    private function requiredColumns(): array
    {
        return [
            'notifications' => ['deleted_at', 'notifiable_type', 'notifiable_id', 'data'],
            'personal_access_tokens' => ['tokenable_type', 'tokenable_id'],
            'stock_movements' => ['movement_type', 'before_quantity', 'after_quantity', 'user_id'],
            'approval_templates' => ['created_by', 'updated_by', 'steps_config'],
            'approval_workflows' => ['created_by', 'updated_by'],
            'plans' => ['status', 'features'],
            'gateways' => ['status', 'charge'],
            'user_permissions' => ['user_id'],
            'app_users' => ['email', 'password'],
            'goods_received_notes' => ['branch_id', 'received_by'],
            'purchase_orders' => ['branch_id', 'created_by'],
            'supplier_payments' => ['branch_id', 'created_by', 'approved_by'],
            'stock_audits' => ['user_id'],
            'incomes' => ['user_id'],
            'expenses' => ['user_id'],
            'recall_events' => ['user_id'],
            'insurance_claims' => ['user_id'],
            'customer_interactions' => ['user_id'],
            'auto_order_suggestions' => ['lead_time_days', 'reorder_point', 'safety_stock'],
        ];
    }

    public function test_the_migrated_schema_has_every_column_the_application_writes(): void
    {
        $missing = [];

        foreach ($this->requiredColumns() as $table => $columns) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Table [{$table}] was not created by the migrations."
            );

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    $missing[] = "{$table}.{$column}";
                }
            }
        }

        $this->assertSame(
            [],
            $missing,
            'These columns are written or queried by the application but no migration '
            .'creates them, so a fresh deploy would lose the data silently or raise 42703: '
            .implode(', ', $missing)
        );
    }

    /**
     * sqlite cannot tell a uuid from a varchar, so the type decisions are asserted
     * against the migration sources — that is where the regression would be
     * reintroduced, and the assertion is identical on every driver.
     *
     * @return array<int, array{0: string, 1: string, 2: string|null}>
     */
    private function declarationContract(): array
    {
        return [
            // [glob, must contain, must NOT contain]
            ['*create_personal_access_tokens_table.php', "uuidMorphs('tokenable')", "->morphs('tokenable')"],
            ['*create_notifications_table.php', "string('notifiable_id')", "->morphs('notifiable')"],
            ['*create_notifications_table.php', "json('data')", "text('data')"],
            ['*create_plans_table.php', "integer('status')", "boolean('status')"],
            ['*create_plans_table.php', "json('features')", "longText('features')"],
            ['*create_gateways_table.php', "boolean('status')", "string('status')"],
            ['*create_gateways_table.php', "double('charge'", "boolean('charge')"],
            ['*create_plan_subscribes_table.php', "json('notes')", "text('notes')"],
            ['*create_purchases_table.php', "json('purchase_data')", "longText('purchase_data')"],
            ['*create_sales_table.php', "json('sale_data')", "longText('sale_data')"],
            ['*create_sales_table.php', "json('meta')", "text('meta')"],
            ['*create_user_permissions_table.php', "uuid('user_id')", "string('user_id'"],
            ['2026_08_01_000001_create_audit_logs_table.php', 'TYPE inet', null],
        ];
    }

    public function test_migrations_declare_the_column_types_production_uses(): void
    {
        foreach ($this->declarationContract() as [$glob, $mustContain, $mustNotContain]) {
            $files = glob(database_path('migrations/'.$glob));

            $this->assertNotEmpty($files, "No migration matched [{$glob}].");

            foreach ($files as $file) {
                $source = file_get_contents($file);
                $name = basename($file);

                $this->assertStringContainsString(
                    $mustContain,
                    $source,
                    "{$name} no longer declares [{$mustContain}]. Production uses that type; "
                    .'a different declaration makes a fresh deploy diverge from it.'
                );

                if ($mustNotContain !== null) {
                    $this->assertStringNotContainsString(
                        $mustNotContain,
                        $source,
                        "{$name} declares [{$mustNotContain}] again, which is the type that "
                        .'diverged from production.'
                    );
                }
            }
        }
    }

    /**
     * Every actor column in this schema references app_users.id, which is a uuid.
     * Casting one to a numeric type turns the id into 0 on read — that is how
     * expenses.user_id silently attributed every expense to nobody.
     */
    public function test_no_model_casts_an_actor_column_to_a_numeric_type(): void
    {
        $actorColumns = [
            'user_id', 'created_by', 'updated_by', 'approved_by', 'rejected_by',
            'received_by', 'verified_by', 'actor_user_id', 'approver_id', 'rated_by',
            'dispensed_by', 'performed_by', 'signed_by', 'checker_id',
        ];

        $numericCasts = ['integer', 'int', 'float', 'double', 'decimal', 'boolean', 'bool'];
        $offenders = [];

        foreach (glob(app_path('Models/*.php')) as $file) {
            $source = file_get_contents($file);

            if (! preg_match('/protected\s+\$casts\s*=\s*\[(.*?)\]\s*;/s', $source, $casts)) {
                continue;
            }

            preg_match_all('/[\'"](\w+)[\'"]\s*=>\s*[\'"]([\w:]+)[\'"]/', $casts[1], $pairs, PREG_SET_ORDER);

            foreach ($pairs as [$match, $column, $cast]) {
                if (in_array($column, $actorColumns, true)
                    && in_array(explode(':', $cast)[0], $numericCasts, true)) {
                    $offenders[] = basename($file, '.php')." casts {$column} to {$cast}";
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Actor columns reference app_users.id (a uuid). A numeric cast coerces the '
            ."id to 0: ".implode('; ', $offenders)
        );
    }

    public function test_stock_movement_keeps_its_audit_trail_fields_assignable(): void
    {
        $model = new StockMovement();

        foreach (['movement_type', 'before_quantity', 'after_quantity', 'user_id', 'created_by'] as $column) {
            $this->assertTrue(
                $model->isFillable($column),
                "stock_movements.{$column} is not fillable, so StockMovement::create() "
                .'discards it and the movement loses that detail.'
            );
        }
    }

    /**
     * The service typed its actor parameters as `int` while writing them into uuid
     * columns, so a caller passing auth()->id() died with a TypeError before any
     * SQL ran, and a caller passing an int died with 22P02 inside the insert.
     */
    public function test_the_approval_service_accepts_a_uuid_actor(): void
    {
        foreach ([
            ['createWorkflow', 3],
            ['approveStep', 2],
            ['rejectStep', 2],
        ] as [$method, $index]) {
            $type = (new ReflectionMethod(ApprovalWorkflowService::class, $method))
                ->getParameters()[$index]
                ->getType();

            $this->assertNotNull($type, "{$method}() has no type on its actor parameter.");
            $this->assertStringContainsString(
                'string',
                (string) $type,
                "{$method}()'s actor parameter is [{$type}] and cannot receive a uuid."
            );
        }
    }
}
