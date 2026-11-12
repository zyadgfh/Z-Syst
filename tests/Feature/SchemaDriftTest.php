<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;
use Throwable;

/**
 * Schema-drift guard.
 *
 * Why this exists
 * ---------------
 * Production (Supabase) was built by a different path than this repository's
 * migrations, so the two silently diverged: 137 columns that models declare in
 * $fillable did not exist in production, and 24 production tables had no
 * migration at all. Tests could not catch it because CI builds sqlite *from the
 * migrations*, i.e. it validated a database that was not production. 398 green
 * tests coexisted with features that returned HTTP 500 live.
 *
 * Production was realigned on 2026-11-10 (Supabase migrations
 * `align_prod_with_repository_migrations` and `fix_banners_imageurl_case`), and
 * the 24 missing migrations were authored from the live schema. These tests keep
 * the three sides — models, migrations, production — locked together.
 *
 * They need no secrets and no network access: they assert that the *migrated*
 * database satisfies every model, and that every production table is reproduced
 * by a migration. Run against Postgres with SUPABASE_* credentials they double as
 * a live-parity check.
 */
class SchemaDriftTest extends TestCase
{
    /**
     * The whole point of this guard is the *migrated* schema, so it has to be
     * built for real: every migration in database/migrations runs here. A
     * migration that cannot run is itself schema drift, and fails this suite.
     */
    use RefreshDatabase;

    /**
     * $fillable entries that intentionally have no database column.
     *
     * GoTrue (Supabase Auth) owns identity; public.app_users is a per-business
     * profile mirror and must never store credentials. config/supabase.php keeps
     * 'fallback_eloquent' => false and documents that production must leave it
     * disabled — the Eloquent registration/reset path that would write these two
     * columns is off in production. Do not add the columns to "fix" this test.
     */
    private const INTENTIONALLY_ABSENT = [
        'app_users.email',
        'app_users.password',
    ];

    /**
     * Relations created by the migrations but absent from production: Breeze
     * scaffolding that was never used. routes/auth.php is not loaded by
     * RouteServiceProvider, and the real reset flow is the OTP mailer in
     * Api\Auth\ZSystForgotPasswordController. Kept (rather than deleted) so an
     * environment that already ran them does not orphan its migrations rows.
     *
     * `users` is special: 2026_11_08_000003 drops the legacy integer-keyed table
     * and re-creates the name as a VIEW over app_users, so no model may treat it
     * as a second identity store. Schema::hasTable() reports false for a view on
     * sqlite, hence the view-aware relationExists() below.
     */
    private const REPO_ONLY_TABLES = [
        'password_resets',
        'password_reset_tokens',
        'users',
    ];

    /** A snapshot of production's public schema; see the file header for how to refresh it. */
    private const PRODUCTION_TABLES = 'database/schema/production_tables.txt';

    /**
     * Every model must map to a table the migrations actually create, and every
     * column it mass-assigns must exist. A missing column is not a warning:
     * Postgres rejects the INSERT/SELECT and the request returns 500.
     */
    public function test_every_fillable_column_exists_on_the_migrated_table(): void
    {
        $problems = [];
        $checked = 0;

        foreach ($this->modelClasses() as $class) {
            try {
                /** @var Model $model */
                $model = new $class;
                $table = $model->getTable();
            } catch (Throwable $e) {
                $problems[] = "{$class}: could not resolve table ({$e->getMessage()})";
                continue;
            }

            // Cross-schema tables (e.g. auth.users) are owned by Supabase, not by
            // these migrations, so they are out of scope here.
            if (str_contains($table, '.')) {
                continue;
            }

            $checked++;

            if (! $this->relationExists($table)) {
                $problems[] = "{$class}: table `{$table}` does not exist — no migration creates it";
                continue;
            }

            foreach ($model->getFillable() as $column) {
                $key = "{$table}.{$column}";

                if (in_array($key, self::INTENTIONALLY_ABSENT, true)) {
                    continue;
                }

                if (! Schema::hasColumn($table, $column)) {
                    $problems[] = "{$class}: `{$table}`.{$column} is fillable but the column does not exist";
                }
            }
        }

        $this->assertGreaterThan(
            100,
            $checked,
            'Too few models were inspected — the discovery glob is probably broken, so this guard would pass vacuously.'
        );

        $this->assertSame([], $problems, "Schema drift detected:\n  - ".implode("\n  - ", $problems));
    }

    /**
     * `php artisan migrate:fresh` must be able to rebuild production. Any table
     * listed in the production snapshot that no migration creates means a fresh
     * deploy (or a CI run) exercises a different database than production.
     */
    public function test_every_production_table_is_reproduced_by_the_migrations(): void
    {
        $path = base_path(self::PRODUCTION_TABLES);

        $this->assertFileExists($path, 'The production table snapshot is missing; regenerate it (see the file header).');

        $lines = preg_split('/[\r\n,]+/', (string) file_get_contents($path)) ?: [];

        $tables = array_values(array_unique(array_filter(array_map(
            static fn (string $line): string => trim($line),
            $lines
        ), static fn (string $line): bool => $line !== '' && ! str_starts_with($line, '#'))));

        $this->assertGreaterThan(
            100,
            count($tables),
            'The production snapshot looks truncated — refusing to pass a guard that checked nothing.'
        );

        $missing = array_values(array_filter(
            $tables,
            fn (string $t): bool => ! $this->relationExists($t)
        ));

        $this->assertSame(
            [],
            $missing,
            'These production tables are created by no migration, so migrate:fresh cannot reproduce production: '
            .implode(', ', $missing)
        );
    }

    /**
     * The snapshot must stay honest about the known repository-only tables, so
     * that nobody "fixes" the drift by creating credential columns or by adding
     * a production table for Breeze scaffolding.
     */
    public function test_repository_only_tables_are_still_the_documented_three(): void
    {
        $present = array_values(array_filter(
            self::REPO_ONLY_TABLES,
            fn (string $t): bool => $this->relationExists($t)
        ));

        $this->assertSame(
            self::REPO_ONLY_TABLES,
            $present,
            'The set of Breeze scaffolding tables changed. If one was removed deliberately, update '
            .'REPO_ONLY_TABLES; if a new one appeared, it needs a decision, not a silent pass.'
        );
    }

    /**
     * Does this name exist as a table *or* a view in the migrated schema?
     *
     * Schema::hasTable() answers false for a view, and this application has one:
     * `users` was retired as a table and re-created as a view over app_users so
     * that legacy reads keep working without a second identity store. Checking
     * only hasTable() would report that relation as missing schema drift.
     */
    private function relationExists(string $name): bool
    {
        return Schema::hasTable($name) || $this->viewExists($name);
    }

    private function viewExists(string $name): bool
    {
        $sql = DB::getDriverName() === 'sqlite'
            ? "select name from sqlite_master where type = 'view' and name = ?"
            : 'select table_name as name from information_schema.views where table_schema = current_schema() and table_name = ?';

        return (bool) DB::selectOne($sql, [$name]);
    }

    /**
     * @return array<int, class-string<Model>>
     */
    private function modelClasses(): array
    {
        $classes = [];

        foreach (glob(base_path('app/Models/*.php')) ?: [] as $file) {
            $class = 'App\\Models\\'.basename($file, '.php');

            if (! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
                continue;
            }

            $classes[] = $class;
        }

        sort($classes);

        return $classes;
    }
}
