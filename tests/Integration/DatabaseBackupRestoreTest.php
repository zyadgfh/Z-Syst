<?php

namespace Tests\Integration;

use App\Services\BackupService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/** Real dump and restore, only in the explicit disposable CI PostgreSQL instance. */
class DatabaseBackupRestoreTest extends TestCase
{
    public function test_postgres_dump_restores_a_known_row_to_a_separate_database(): void
    {
        if (getenv('ZSYST_DISPOSABLE_DATABASE') !== 'yes' || getenv('APP_ENV') !== 'testing'
            || getenv('DB_CONNECTION') !== 'pgsql' || getenv('DB_HOST') !== '127.0.0.1'
            || getenv('DB_DATABASE') !== 'zsyst_test' || getenv('DATABASE_URL')) {
            $this->markTestSkipped('Requires the explicit disposable PostgreSQL test instance.');
        }
        $suffix = bin2hex(random_bytes(6));
        $table = 'backup_test_'.$suffix;
        $target = 'zsyst_restore_'.$suffix;
        $created = false;
        $path = null;
        try {
            DB::statement('CREATE TABLE "'.$table.'" (id integer primary key, value text not null)');
            DB::insert('INSERT INTO "'.$table.'" (id, value) VALUES (?, ?)', [1, 'restore-proof']);
            $path = app(BackupService::class)->createDatabaseBackup();
            $this->assertNotEmpty(hash_file('sha256', $path));
            DB::statement('CREATE DATABASE "'.$target.'"');
            $created = true;
            $config = DB::connection()->getConfig();
            $env = ['PGPASSWORD' => (string) $config['password'], 'PGSSLMODE' => (string) ($config['sslmode'] ?? 'require')];
            $common = ['--host='.$config['host'], '--port='.$config['port'], '--username='.$config['username'], '--dbname='.$target];
            $restore = new Process(array_merge(['psql', '--no-password', '--no-psqlrc', '--set=ON_ERROR_STOP=1'], $common, ['--file='.$path]), null, $env, null, 120);
            $restore->run();
            $this->assertTrue($restore->isSuccessful(), 'Restore failed: '.$restore->getErrorOutput());
            $verify = new Process(array_merge(['psql', '--no-password', '--no-psqlrc', '--tuples-only', '--no-align'], $common, ['--command=SELECT value FROM "'.$table.'" WHERE id=1']), null, $env, null, 30);
            $verify->run();
            $this->assertTrue($verify->isSuccessful(), 'Verification query failed: '.$verify->getErrorOutput());
            $this->assertSame('restore-proof', trim($verify->getOutput()));
        } finally {
            if ($path && is_file($path)) {
                unlink($path);
            }
            DB::statement('DROP TABLE IF EXISTS "'.$table.'"');
            if ($created) {
                DB::statement('DROP DATABASE "'.$target.'"');
            }
        }
    }
}
