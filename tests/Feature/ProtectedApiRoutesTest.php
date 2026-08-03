<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProtectedApiRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_endpoint_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/backup');

        $response->assertStatus(401);
    }

    public function test_profile_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(401);
    }

    public function test_purchase_report_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/purchase-report');

        $response->assertStatus(401);
    }
}
