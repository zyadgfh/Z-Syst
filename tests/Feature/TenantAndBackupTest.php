<?php

namespace Tests\Feature;

use App\Services\TenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

    public function test_database_backup_command_creates_a_backup_file(): void
    {
        Storage::fake('local');

        $this->artisan('backup:database', ['--disk' => 'local'])
            ->expectsOutputToContain('Backup created')
            ->assertSuccessful();

        $files = Storage::disk('local')->allFiles('backups');
        $this->assertNotEmpty($files);
    }
}
