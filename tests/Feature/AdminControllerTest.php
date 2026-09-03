<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControllerTest extends TestCase
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

        // Seed comprehensive permissions
        $perms = [
            'dashboard-read',
            'coupons-read', 'coupons-create', 'coupons-update', 'coupons-delete',
            'orders-read', 'orders-update',
            'products-view', 'products-create', 'products-edit', 'products-delete',
            'settings-view', 'settings-edit',
            'banners-read', 'banners-create', 'banners-update', 'banners-delete',
            'business-categories-read', 'business-categories-create', 'business-categories-update', 'business-categories-delete',
            'business-read', 'business-create', 'business-update', 'business-delete',
            'currencies-read', 'currencies-create', 'currencies-update', 'currencies-delete',
            'plans-read', 'plans-create', 'plans-update', 'plans-delete',
            'users-read', 'users-create', 'users-update', 'users-delete',
            'gateways-read', 'gateways-create', 'gateways-update', 'gateways-delete',
            'inventory-read',
            'online-store-read',
            'reviews-read',
            'prescriptions-read',
            'suppliers-read', 'suppliers-create', 'suppliers-update',
            'loyalty-read',
            'traceability-read',
            'purchase-orders-read',
            'permissions-read',
            'warehouses-read',
            'maintenance-read',
            'payment-gateways-read',
            'roles-read',
            'grn-read',
            'security-read',
            'analytics-read',
            'comparison-analytics-read',
            'receipts-read',
            'stock-transfers-read',
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

    // ─── Dashboard ──────────────────────────────────────

    /** @test */
    public function test_dashboard_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.dashboard.index'));
        $response->assertStatus(200);
        $response->assertSee(__('dashboard.Dashboard'));
    }

    // ─── Coupons ────────────────────────────────────────

    /** @test */
    public function test_coupons_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.coupons.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function test_coupons_create_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.coupons.create'));
        $response->assertStatus(200);
    }

    // ─── Customer Orders ────────────────────────────────

    /** @test */
    public function test_customer_orders_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.customer-orders.index'));
        $response->assertStatus(200);
    }

    // ─── Banners ────────────────────────────────────────

    /** @test */
    public function test_banners_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.banners.index'));
        $response->assertStatus(200);
    }

    // ─── Business Categories ────────────────────────────

    /** @test */
    public function test_business_categories_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.business-categories.index'));
        $response->assertStatus(200);
    }

    // ─── Business ───────────────────────────────────────

    /** @test */
    public function test_business_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.business.index'));
        $response->assertStatus(200);
    }

    // ─── Currencies ─────────────────────────────────────

    /** @test */
    public function test_currencies_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.currencies.index'));
        $response->assertStatus(200);
    }

    // ─── Plans ──────────────────────────────────────────

    /** @test */
    public function test_plans_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.plans.index'));
        $response->assertStatus(200);
    }

    // ─── Gateways ───────────────────────────────────────

    /** @test */
    public function test_gateways_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.gateways.index'));
        $response->assertStatus(200);
    }

    // ─── Online Store ──────────────────────────────────

    /** @test */
    public function test_online_store_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.online-store.index'));
        $response->assertStatus(200);
    }

    // ─── Users ──────────────────────────────────────────

    /** @test */
    public function test_users_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.users.index'));
        $response->assertStatus(200);
    }

    // ─── Push Notifications ─────────────────────────────

    /** @test */
    public function test_push_notifications_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.push-notifications.index'));
        $response->assertStatus(200);
    }

    // ─── Warehouses ─────────────────────────────────────

    /** @test */
    public function test_warehouses_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.warehouses.index'));
        $response->assertStatus(200);
    }

    // ─── Roles ──────────────────────────────────────────

    /** @test */
    public function test_roles_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.roles.index'));
        $response->assertStatus(200);
    }

    // ─── Barcodes ───────────────────────────────────────

    /** @test */
    public function test_barcodes_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.barcodes.index'));
        $response->assertStatus(200);
    }

    // ─── Profiles ───────────────────────────────────────

    /** @test */
    public function test_profiles_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.profiles.index'));
        $response->assertStatus(200);
    }

    // ─── Onboarding ─────────────────────────────────────

    /** @test */
    public function test_onboarding_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.onboarding.index'));
        $response->assertStatus(200);
    }

    // ─── Manual Payments ────────────────────────────────

    /** @test */
    public function test_manual_payments_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.manual-payments.index'));
        $response->assertStatus(200);
    }
}
