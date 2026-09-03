<?php

namespace Tests\Unit\Policies;

use App\Models\Business;
use App\Models\SupplierInvoice;
use App\Models\User;
use App\Policies\SupplierInvoicePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SupplierInvoicePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected SupplierInvoicePolicy $policy;
    protected User $user;
    protected User $otherUser;
    protected Business $business;
    protected SupplierInvoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new SupplierInvoicePolicy();

        // Seed permissions
        Permission::create(['name' => 'purchases-view', 'guard_name' => 'web']);
        Permission::create(['name' => 'purchases-create', 'guard_name' => 'web']);
        Permission::create(['name' => 'purchases-edit', 'guard_name' => 'web']);
        Permission::create(['name' => 'purchases-delete', 'guard_name' => 'web']);
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();

        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->otherUser = User::factory()->create(['business_id' => $otherBusiness->id]);

        $this->invoice = SupplierInvoice::factory()->create([
            'business_id' => $this->business->id,
        ]);
    }

    public function test_view_any_denies_by_default(): void
    {
        $this->assertFalse($this->policy->viewAny($this->user));
    }

    public function test_view_any_allows_with_permission(): void
    {
        $this->user->givePermissionTo('purchases-view');
        $this->assertTrue($this->policy->viewAny($this->user));
    }

    public function test_view_denies_by_default(): void
    {
        $this->assertFalse($this->policy->view($this->user, $this->invoice));
    }

    public function test_view_allows_with_permission(): void
    {
        $this->user->givePermissionTo('purchases-view');
        $this->assertTrue($this->policy->view($this->user, $this->invoice));
    }

    public function test_create_denies_by_default(): void
    {
        $this->assertFalse($this->policy->create($this->user));
    }

    public function test_create_allows_with_permission(): void
    {
        $this->user->givePermissionTo('purchases-create');
        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_update_denies_by_default(): void
    {
        $this->assertFalse($this->policy->update($this->user, $this->invoice));
    }

    public function test_update_allows_with_permission(): void
    {
        $this->user->givePermissionTo('purchases-edit');
        $this->assertTrue($this->policy->update($this->user, $this->invoice));
    }

    public function test_delete_denies_by_default(): void
    {
        $this->assertFalse($this->policy->delete($this->user, $this->invoice));
    }

    public function test_delete_allows_with_permission(): void
    {
        $this->user->givePermissionTo('purchases-delete');
        $this->assertTrue($this->policy->delete($this->user, $this->invoice));
    }
}
