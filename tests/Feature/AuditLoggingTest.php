<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_update_is_logged_to_audit_log(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create([
            'business_id' => $business->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile', [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
            ])
            ->assertStatus(200);

        $auditLog = AuditLog::where('user_id', $user->id)
            ->where('action', 'profile.updated')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals('User profile updated.', $auditLog->description);
    }

    public function test_password_change_is_logged_to_audit_log(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create([
            'business_id' => $business->id,
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/change-password', [
                'current_password' => 'password',
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
            ])
            ->assertStatus(200);

        $auditLog = AuditLog::where('user_id', $user->id)
            ->where('action', 'password.changed')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals('User password changed.', $auditLog->description);
    }

    public function test_backup_action_is_logged_to_audit_log(): void
    {
        if (! class_exists(Role::class)) {
            $this->markTestSkipped('Spatie Role model not found');
        }

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $business = Business::factory()->create();
        $user = User::factory()->create([
            'business_id' => $business->id,
        ]);
        $user->assignRole('superadmin');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/backup')
            ->assertStatus(200);

        $auditLog = AuditLog::where('user_id', $user->id)
            ->where('action', 'backup.created')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals('Database backup created by administrator.', $auditLog->description);
    }
}
