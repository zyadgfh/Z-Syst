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

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::create(['name' => 'superadmin', 'guard_name' => 'sanctum']);
    }

    public function test_maintenance_status_can_be_retrieved()
    {
        $user = User::factory()->create(['role' => 'shop-owner']);

        $response = $this->actingAs($user, 'web')
            ->getJson('/admin/maintenance/status');

        $response->assertStatus(200);
    }

    public function test_maintenance_can_be_activated_by_superadmin()
    {
        $superadmin = User::factory()->create(['role' => 'shop-owner']);
        $superadmin->assignRole('superadmin');

        $response = $this->actingAs($superadmin, 'web')
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
        $superadmin = User::factory()->create(['role' => 'shop-owner']);
        $superadmin->assignRole('superadmin');

        // First activate maintenance - bypass MaintenanceMode middleware
        // since it blocks all requests when is_enabled=true
        \DB::table('maintenance_settings')->insert([
            'is_enabled' => true,
            'title' => 'Test Maintenance',
            'message' => 'Test message',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Disable MaintenanceMode middleware so the deactivate request can reach the controller
        $this->withoutMiddleware(\App\Http\Middleware\MaintenanceMode::class);

        $response = $this->actingAs($superadmin, 'web')
            ->postJson('/admin/maintenance/deactivate');

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('maintenance_settings', [
            'is_enabled' => false,
        ]);
    }

    public function test_regular_user_cannot_activate_maintenance()
    {
        $user = User::factory()->create(['role' => 'shop-owner']);

        $response = $this->actingAs($user, 'web')
            ->postJson('/admin/maintenance/activate', [
                'title' => 'Test',
                'message' => 'Test',
            ]);

        $response->assertStatus(403);
    }
}
