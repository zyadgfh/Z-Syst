<?php

namespace Tests\Unit\Policies;

use App\Models\Business;
use App\Models\StockTransfer;
use App\Models\User;
use App\Policies\StockTransferPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StockTransferPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected StockTransferPolicy $policy;
    protected User $user;
    protected User $otherUser;
    protected Business $business;
    protected StockTransfer $transfer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new StockTransferPolicy();

        // Seed permissions
        Permission::create(['name' => 'inventory-view', 'guard_name' => 'web']);
        Permission::create(['name' => 'inventory-create', 'guard_name' => 'web']);
        Permission::create(['name' => 'inventory-edit', 'guard_name' => 'web']);
        Permission::create(['name' => 'inventory-delete', 'guard_name' => 'web']);
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();

        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->otherUser = User::factory()->create(['business_id' => $otherBusiness->id]);

        $this->transfer = StockTransfer::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'pending',
        ]);
    }

    public function test_view_any_denies_by_default(): void
    {
        $this->assertFalse($this->policy->viewAny($this->user));
    }

    public function test_view_any_allows_with_permission(): void
    {
        $this->user->givePermissionTo('inventory-view');
        $this->assertTrue($this->policy->viewAny($this->user));
    }

    public function test_view_denies_by_default(): void
    {
        $this->assertFalse($this->policy->view($this->user, $this->transfer));
    }

    public function test_view_allows_with_permission(): void
    {
        $this->user->givePermissionTo('inventory-view');
        $this->assertTrue($this->policy->view($this->user, $this->transfer));
    }

    public function test_create_denies_by_default(): void
    {
        $this->assertFalse($this->policy->create($this->user));
    }

    public function test_create_allows_with_permission(): void
    {
        $this->user->givePermissionTo('inventory-create');
        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_update_denies_by_default(): void
    {
        $this->assertFalse($this->policy->update($this->user, $this->transfer));
    }

    public function test_update_allows_with_permission(): void
    {
        $this->user->givePermissionTo('inventory-edit');
        $this->assertTrue($this->policy->update($this->user, $this->transfer));
    }

    public function test_delete_denies_by_default(): void
    {
        $this->assertFalse($this->policy->delete($this->user, $this->transfer));
    }

    public function test_delete_allows_with_permission(): void
    {
        $this->user->givePermissionTo('inventory-delete');
        $this->assertTrue($this->policy->delete($this->user, $this->transfer));
    }

    public function test_complete_denies_by_default(): void
    {
        $this->assertFalse($this->policy->complete($this->user, $this->transfer));
    }

    public function test_complete_allows_with_permission(): void
    {
        $this->user->givePermissionTo('inventory-edit');
        $this->assertTrue($this->policy->complete($this->user, $this->transfer));
    }

    public function test_cancel_denies_by_default(): void
    {
        $this->assertFalse($this->policy->cancel($this->user, $this->transfer));
    }

    public function test_cancel_allows_with_permission(): void
    {
        $this->user->givePermissionTo('inventory-edit');
        $this->assertTrue($this->policy->cancel($this->user, $this->transfer));
    }
}
