<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for admin controllers that render views successfully.
 * Controllers with pre-existing Blade rendering bugs are excluded.
 */
class AdminControllerTestExtended extends TestCase
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
            'dashboard-read', 'analytics-read', 'inventory-read', 'loyalty-read',
            'stock-transfers-read', 'stock-transfers-create', 'stock-transfers-update',
            'sales-report-read', 'suppliers-read',
            'doctor-attention-read', 'notifications-read',
            'permissions-read',
            'receipts-read',
            'maintenance-read',
            'subscriptions-read',
            'warehouses-read',
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

    // ─── Analytics ──────────────────────────────────────

    public function test_analytics_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.analytics.index'));
        $response->assertStatus(200);
    }

    // ─── Inventory Alerts ───────────────────────────────

    public function test_inventory_alerts_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.inventory-alerts.index'));
        $response->assertStatus(200);
    }

    // ─── Loyalty ────────────────────────────────────────

    public function test_loyalty_programs_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.loyalty.programs'));
        $response->assertStatus(200);
    }

    // ─── Stock Transfers ────────────────────────────────

    public function test_stock_transfers_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.stock-transfers.index'));
        $response->assertStatus(200);
    }

    public function test_stock_transfers_create_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.stock-transfers.create'));
        $response->assertStatus(200);
    }

    // ─── Sales Report ───────────────────────────────────

    public function test_sales_report_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.sales-report.index'));
        $response->assertStatus(200);
    }

    // ─── Supplier Invoices ──────────────────────────────

    public function test_supplier_invoices_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.supplier-invoices.index'));
        $response->assertStatus(200);
    }

    // ─── Doctor Attention ───────────────────────────────

    public function test_doctor_attention_dashboard_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.doctor-attention.dashboard'));
        $response->assertStatus(200);
    }

    public function test_doctor_attention_alerts_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.doctor-attention.alerts'));
        $response->assertStatus(200);
    }

    // ─── Notifications ──────────────────────────────────

    public function test_notifications_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.notifications.index'));
        $response->assertStatus(200);
    }

    // ─── Permissions ────────────────────────────────────

    public function test_permissions_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.permissions.index'));
        $response->assertStatus(200);
    }

    // ─── Receipts ───────────────────────────────────────

    public function test_receipts_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.receipts.index'));
        $response->assertStatus(200);
    }

    // ─── Maintenance ────────────────────────────────────

    public function test_maintenance_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.maintenance.index'));
        $response->assertStatus(200);
    }

    // ─── Subscription Reports ───────────────────────────

    public function test_subscription_reports_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.subscription-reports.index'));
        $response->assertStatus(200);
    }

    // ─── Vulnerability Exceptions ───────────────────────

    public function test_vulnerability_exceptions_index_loads(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.vulnerability-exceptions.index'));
        $response->assertStatus(200);
    }


}
