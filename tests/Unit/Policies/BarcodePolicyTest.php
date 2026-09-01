<?php

namespace Tests\Unit\Policies;

use App\Models\Barcode;
use App\Models\Business;
use App\Models\User;
use App\Policies\BarcodePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BarcodePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected BarcodePolicy $policy;
    protected User $user;
    protected User $otherUser;
    protected Barcode $barcode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new BarcodePolicy();

        // Seed permissions
        Permission::create(['name' => 'products-view', 'guard_name' => 'web']);
        Permission::create(['name' => 'products-create', 'guard_name' => 'web']);
        Permission::create(['name' => 'products-edit', 'guard_name' => 'web']);
        Permission::create(['name' => 'products-delete', 'guard_name' => 'web']);
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();

        $this->user = User::factory()->create(['business_id' => $business->id]);
        $this->otherUser = User::factory()->create(['business_id' => $otherBusiness->id]);

        $this->barcode = Barcode::factory()->create(['business_id' => $business->id]);
    }

    public function test_view_any_denies_by_default(): void
    {
        $this->assertFalse($this->policy->viewAny($this->user));
    }

    public function test_view_any_allows_with_permission(): void
    {
        $this->user->givePermissionTo('products-view');
        $this->assertTrue($this->policy->viewAny($this->user));
    }

    public function test_view_denies_by_default(): void
    {
        $this->assertFalse($this->policy->view($this->user, $this->barcode));
    }

    public function test_view_allows_with_permission(): void
    {
        $this->user->givePermissionTo('products-view');
        $this->assertTrue($this->policy->view($this->user, $this->barcode));
    }

    public function test_create_denies_by_default(): void
    {
        $this->assertFalse($this->policy->create($this->user));
    }

    public function test_create_allows_with_permission(): void
    {
        $this->user->givePermissionTo('products-create');
        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_update_denies_by_default(): void
    {
        $this->assertFalse($this->policy->update($this->user, $this->barcode));
    }

    public function test_update_allows_with_permission(): void
    {
        $this->user->givePermissionTo('products-edit');
        $this->assertTrue($this->policy->update($this->user, $this->barcode));
    }

    public function test_delete_denies_by_default(): void
    {
        $this->assertFalse($this->policy->delete($this->user, $this->barcode));
    }

    public function test_delete_allows_with_permission(): void
    {
        $this->user->givePermissionTo('products-delete');
        $this->assertTrue($this->policy->delete($this->user, $this->barcode));
    }

    public function test_print_denies_by_default(): void
    {
        $this->assertFalse($this->policy->print($this->user));
    }

    public function test_print_allows_with_permission(): void
    {
        $this->user->givePermissionTo('products-view');
        $this->assertTrue($this->policy->print($this->user));
    }
}
