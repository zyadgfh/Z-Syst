# Quality testing status

The `Laravel upgrade validation` workflow runs the existing PHPUnit suite separately
against SQLite, PostgreSQL and MySQL using disposable GitHub Actions service
containers. It also runs on pushes to Arena branches. The report and bounded
annotations describe failures without converting them into successful checks.
Do not point test configurations at a production database: database-refreshing tests
can drop or overwrite data.

The locked `supabase-php/supabase-client` package was removed because its source
returns 404 and its `Supabase\\Client\\` namespace does not provide the
`Supabase\\SupabaseClient`, `Supabase\\Gotrue\\Client`, or
`Supabase\\Storage\\StorageClient` classes referenced by this application.
This change does **not** restore those older Supabase service integrations; they
require a compatible implementation and separate tests before use.

Existing feature/integration tests include sale, purchase, returns, stock and
supplier flows; their presence is not proof of coverage or success. Browser E2E,
concurrent end-to-end sale of the last batch item, physical Flutter devices,
and representative load tests remain outstanding. Run performance tests only
against an isolated seeded environment, never the production system. Physical
device execution requires a connected device or device farm and credentials
managed outside the repository. No production balances are reconciled by CI.

The concurrency regression uses two independent PHP processes against a single
committed batch and requires `APP_ENV=testing`, `DB_HOST=127.0.0.1`,
`DB_DATABASE=zsyst_test`, `ZSYST_DISPOSABLE_DATABASE=yes`, and no
`DATABASE_URL`. `DatabaseMigrations` destroys the disposable database schema;
never use a database containing data you need. SQLite intentionally skips
this test because it does not provide the same row-locking semantics. The test
covers allocation rather than an entire sale HTTP flow.

Flutter's hosted-runner `flutter test` is not a physical-device test. The
initial Flutter dependency install failed under 3.24.3 because the checked-in
lockfile requires Dart 3.10 and Flutter 3.27 or newer; CI now uses stable Flutter.
