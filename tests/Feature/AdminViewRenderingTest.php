<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminViewRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::factory()->create();
        $this->business = Business::factory()->create([
            'business_category_id' => $category->id,
        ]);

        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'role' => 'shop-owner',
        ]);

        $perms = [
            'products-view', 'items-view', 'suppliers-read', 'suppliers-create',
            'purchase-orders-read', 'purchases-view', 'purchases-create',
            'insurance-read', 'insurance-create',
            'insurance-companies-read', 'insurance-companies-create',
            'insurance-policies-read', 'insurance-policies-create',
            'insurance-claims-read', 'insurance-claims-create',
            'grn-read', 'grn-create', 'payment-gateways-read', 'subscriptions-read',
        ];

        foreach ($perms as $p) {
            \Spatie\Permission\Models\Permission::create(['name' => $p, 'guard_name' => 'web']);
        }
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $role = \Spatie\Permission\Models\Role::create(['name' => 'shop-owner', 'guard_name' => 'web']);
        $role->syncPermissions($perms);
        $this->owner->assignRole('shop-owner');
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_products_redirects_to_items(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.products.index'));
        $response->assertRedirect();
    }

    public function test_items_index(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.items.index'));
        $response->assertStatus(200);
    }

    public function test_suppliers_index(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.suppliers.index'));
        $response->assertStatus(200);
    }

    public function test_suppliers_create(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.suppliers.create'));
        $response->assertStatus(200);
    }

    public function test_purchase_orders_index(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.purchase-orders.index'));
        $response->assertStatus(200);
    }

    public function test_purchases_index(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.purchases.index'));
        $response->assertStatus(200);
    }

    public function test_insurance_companies_index(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.insurance.companies.index'));
        $response->assertStatus(200);
    }

    public function test_insurance_policies_index(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.insurance.policies.index'));
        $response->assertStatus(200);
    }

    public function test_insurance_claims_index(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.insurance.claims.index'));
        $response->assertStatus(200);
    }

    public function test_grn_index(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.grn.index'));
        $response->assertStatus(200);
    }

    public function test_payment_gateways_index(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.payment-gateways.index'));
        $response->assertStatus(200);
    }

    public function test_subscriptions_index(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.subscriptions.index'));
        $response->assertStatus(200);
    }
}
