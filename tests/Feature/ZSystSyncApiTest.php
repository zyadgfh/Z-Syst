<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ZSystSyncApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh', ['--path' => 'Modules/ZSyst/Database/migrations', '--force' => true]);
    }

    public function test_online_and_offline_sync_status_can_be_recorded(): void
    {
        $response = $this->postJson('/api/zsyst/sync/heartbeat', [
            'device_id' => 'mobile-1',
            'status' => 'online',
            'last_sync_at' => now()->toIso8601String(),
        ]);

        $response->assertCreated();
        $response->assertJsonFragment(['status' => 'online']);
    }
}
