<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BackupAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_superadmin_cannot_create_backup()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/backup')
            ->assertStatus(403);
    }

    public function test_superadmin_can_create_backup()
    {
        if (! class_exists(Role::class)) {
            $this->markTestSkipped('Spatie Role model not found');
        }

        // Create role and user
        Role::firstOrCreate(['name' => 'superadmin']);
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        // Mock Artisan call
        Artisan::shouldReceive('call')->once()->with('backup:database', ['--disk' => 'local'])->andReturn(0);
        Artisan::shouldReceive('output')->andReturn('backup OK');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/backup')
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Backup created successfully.']);
    }
    public function test_failed_command_is_not_reported_as_a_successful_backup(): void
    {
        Role::firstOrCreate(['name' => 'superadmin']);
        $user = User::factory()->create();
        $user->assignRole('superadmin');
        Artisan::shouldReceive('call')->once()->with('backup:database', ['--disk' => 'local'])->andReturn(1);
        Artisan::shouldNotReceive('output');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/backup')
            ->assertStatus(500)
            ->assertJsonFragment(['message' => 'Failed to create backup.']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'backup.created', 'user_id' => $user->id]);
    }

}
