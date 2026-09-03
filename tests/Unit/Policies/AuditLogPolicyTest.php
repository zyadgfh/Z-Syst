<?php

namespace Tests\Unit\Policies;

use App\Models\AuditLog;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuditLogPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected AuditLogPolicy $policy;
    protected User $user;
    protected User $otherUser;
    protected AuditLog $auditLog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new AuditLogPolicy();

        // Seed permissions
        Permission::create(['name' => 'audit-logs-read', 'guard_name' => 'web']);
        Permission::create(['name' => 'audit-logs-show', 'guard_name' => 'web']);
        Permission::create(['name' => 'audit-logs-delete', 'guard_name' => 'web']);
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $business = \App\Models\Business::factory()->create();
        $otherBusiness = \App\Models\Business::factory()->create();

        $this->user = User::factory()->create(['business_id' => $business->id]);
        $this->otherUser = User::factory()->create(['business_id' => $otherBusiness->id]);

        $this->auditLog = AuditLog::create([
            'user_id' => $this->user->id,
            'action' => 'test_action',
            'description' => 'Test audit log',
        ]);
    }

    public function test_view_any_denies_by_default(): void
    {
        $this->assertFalse($this->policy->viewAny($this->user));
    }

    public function test_view_any_allows_with_permission(): void
    {
        $this->user->givePermissionTo('audit-logs-read');
        $this->assertTrue($this->policy->viewAny($this->user));
    }

    public function test_view_denies_by_default(): void
    {
        $this->assertFalse($this->policy->view($this->user, $this->auditLog));
    }

    public function test_view_allows_with_permission(): void
    {
        $this->user->givePermissionTo('audit-logs-show');
        $this->assertTrue($this->policy->view($this->user, $this->auditLog));
    }

    public function test_delete_denies_by_default(): void
    {
        $this->assertFalse($this->policy->delete($this->user, $this->auditLog));
    }

    public function test_delete_allows_with_permission(): void
    {
        $this->user->givePermissionTo('audit-logs-delete');
        $this->assertTrue($this->policy->delete($this->user, $this->auditLog));
    }
}
