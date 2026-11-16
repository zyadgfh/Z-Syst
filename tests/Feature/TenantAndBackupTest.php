<?php

namespace Tests\Feature;

use App\Services\TenantResolver;
use Illuminate\Http\Request;
use App\Services\BackupService;
use Tests\TestCase;

class TenantAndBackupTest extends TestCase
{
    public function test_tenant_resolver_uses_authenticated_business_id(): void
    {
        $resolver = new TenantResolver;
        $request = Request::create('/');
        $request->setUserResolver(fn () => new class
        {
            public int $business_id = 42;
        });

        $this->assertSame(42, $resolver->resolve($request));
    }

    public function test_database_backup_command_reports_success_only_after_service_succeeds(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'backup-test-');
        file_put_contents($path, 'CREATE TABLE test (id integer);');
        try {
            $service = \Mockery::mock(BackupService::class);
            $service->shouldReceive('createDatabaseBackup')->once()->andReturn($path);
            $this->app->instance(BackupService::class, $service);
            $this->artisan('backup:database', ['--disk' => 'local'])
                ->expectsOutputToContain('Backup created')
                ->assertSuccessful();
        } finally {
            unlink($path);
        }
    }

    public function test_database_backup_command_returns_failure_if_dump_fails(): void
    {
        $service = \Mockery::mock(BackupService::class);
        $service->shouldReceive('createDatabaseBackup')->once()->andThrow(new \RuntimeException('dump failed'));
        $this->app->instance(BackupService::class, $service);
        $this->artisan('backup:database', ['--disk' => 'local'])->assertFailed();
    }

    public function test_unsupported_database_driver_fails_closed(): void
    {
        config(['database.default' => 'sqlite']);
        $this->expectException(\RuntimeException::class);
        app(BackupService::class)->createDatabaseBackup();
    }
}
