<?php

namespace Tests\Feature;

use App\Models\MaintenanceSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_maintenance_status_can_be_retrieved()
    {
        $response = $this->getJson('/admin/maintenance/status');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'is_enabled',
                'title',
                'message',
            ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::create(['name' => 'superadmin', 'guard_name' => 'sanctum']);
    }

    public function test_maintenance_can_be_activated_by_superadmin()
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $response = $this->actingAs($superadmin, 'sanctum')
            ->postJson('/admin/maintenance/activate', [
                'title' => 'Scheduled Maintenance',
                'message' => 'System will be down for maintenance',
                'estimated_duration_minutes' => 30,
            ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('maintenance_settings', [
            'title' => 'Scheduled Maintenance',
            'is_enabled' => true,
        ]);
    }

    public function test_maintenance_can_be_deactivated_by_superadmin()
    {
        $superadmin = User::factory()->create(['is_superadmin' => true]);
        $superadmin->assignRole('superadmin');

        // First activate maintenance
        MaintenanceSetting::create([
            'is_enabled' => true,
            'title' => 'Test Maintenance',
            'message' => 'Test message',
        ]);

        $response = $this->actingAs($superadmin, 'sanctum')
            ->postJson('/admin/maintenance/deactivate');

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('maintenance_settings', [
            'is_enabled' => false,
        ]);
    }

    public function test_regular_user_cannot_activate_maintenance()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/admin/maintenance/activate', [
                'title' => 'Test',
                'message' => 'Test',
            ]);

        $response->assertStatus(403);
    }
}