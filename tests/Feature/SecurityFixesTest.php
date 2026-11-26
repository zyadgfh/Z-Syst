<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Subscription;
use App\Models\User;
use App\Support\SqlDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Regression tests for the 2026-11-26 Code-VulnScan findings
 * (Vulnscan_results.md → F-01 … F-08).
 *
 * RED first: every test here asserts behaviour the codebase did not have
 * when the report was written — server-derived sale aggregates (F-01),
 * platform-route gates (F-02/F-03/F-05), tenant-pinned supplier-invoice
 * foreign keys (F-04), throttled payment initiation (F-06), safe SQL date
 * formatting (F-07) and sale_date format validation (F-08).
 */
class SecurityFixesTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create([
            'business_id' => $role === 'superadmin' ? null : $this->business->id,
            'role' => $role,
        ]);
    }

    /** Product priced 10 (sale) / 6 (cost) with one in-stock batch "B1". */
    private function saleProduct(): Product
    {
        $product = Product::factory()->create([
            'business_id' => $this->business->id,
            'sales_price' => 10,
            'purchase_with_tax' => 6,
        ]);

        Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'product_stock' => 10,
            'batch_no' => 'B1',
        ]);

        return $product;
    }

    private function subscriptionForBusiness(): Subscription
    {
        $plan = Plan::factory()->create();

        return Subscription::create([
            'business_id' => $this->business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    // ------------------------------------------------------------------
    // F-01 — sale aggregates are server-derived (High, CWE-840)
    // ------------------------------------------------------------------

    public function test_sale_aggregates_are_server_derived_not_client_supplied(): void
    {
        $staff = $this->userWithRole('staff');
        $product = $this->saleProduct();

        $response = $this->actingAs($staff)->postJson('/api/v1/sales', [
            'sale_date' => now()->toDateTimeString(),
            // Forged header aggregates — must all be ignored:
            'total_amount' => 0,
            'loss_profit' => 999999,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'paid_amount' => 0,
            'is_paid' => true,
            'products' => [[
                'product_id' => $product->id,
                'price' => 10,
                'quantities' => 2,
                'batch_no' => 'B1',
                // Forged line aggregates — must all be ignored:
                'loss_profit' => 999999,
                'purchase_price' => 0.01,
            ]],
        ]);

        $response->assertOk();

        $saleId = $response->json('data.id');

        // 10 × 2 − 0 + 0 = 20 (client sent 0)
        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'total_amount' => 20,
            // (10 − 6) × 2 − 0 = 8 (client sent 999999)
            'loss_profit' => 8,
        ]);

        // Cost comes from the catalog, profit from price − cost:
        $this->assertDatabaseHas('sale_details', [
            'sale_id' => $saleId,
            'purchase_price' => 6,
            'loss_profit' => 8,
        ]);
    }

    public function test_sale_discount_is_clamped_to_line_subtotal(): void
    {
        $staff = $this->userWithRole('staff');
        $product = $this->saleProduct();

        $response = $this->actingAs($staff)->postJson('/api/v1/sales', [
            'sale_date' => now()->toDateTimeString(),
            'discount_amount' => 9999,   // exceeds the 20 EGP subtotal → clamp to 20
            'total_amount' => -500,      // forged negative total → ignored
            'paid_amount' => 0,
            'products' => [[
                'product_id' => $product->id,
                'price' => 10,
                'quantities' => 2,
                'batch_no' => 'B1',
                'loss_profit' => 0,
            ]],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('sales', [
            'id' => $response->json('data.id'),
            'total_amount' => 0, // 20 − min(9999, 20) + 0
        ]);
    }

    public function test_sale_rejects_malformed_sale_date(): void
    {
        $staff = $this->userWithRole('staff');
        $product = $this->saleProduct();

        $this->actingAs($staff)->postJson('/api/v1/sales', [
            'sale_date' => 'not-a-date',
            'products' => [[
                'product_id' => $product->id,
                'price' => 10,
                'quantities' => 1,
                'batch_no' => 'B1',
                'loss_profit' => 4,
            ]],
        ])->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // F-02 — business-categories mutations are gated (Medium, CWE-862)
    // ------------------------------------------------------------------

    public function test_tenant_admin_cannot_mutate_platform_business_categories(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->postJson('/admin/business-categories', ['name' => 'Should Fail '.uniqid()])
            ->assertForbidden();
    }

    public function test_tenant_admin_cannot_filter_platform_business_categories(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->postJson('/admin/business-categories/filter', [])
            ->assertForbidden();
    }

    public function test_superadmin_can_create_business_category(): void
    {
        $root = $this->userWithRole('superadmin');

        $this->actingAs($root)
            ->postJson('/admin/business-categories', ['name' => 'Category '.uniqid()])
            ->assertOk();
    }

    // ------------------------------------------------------------------
    // F-03 — API subscription mutations require an owner-tier role
    // (Medium, CWE-862 — documented PR #34 follow-up)
    // ------------------------------------------------------------------

    public function test_staff_cannot_cancel_api_subscription(): void
    {
        $staff = $this->userWithRole('staff');
        $subscription = $this->subscriptionForBusiness();

        $this->actingAs($staff)
            ->postJson("/api/v1/subscriptions/{$subscription->id}/cancel")
            ->assertForbidden();
    }

    public function test_shop_owner_passes_the_subscription_gate(): void
    {
        $owner = $this->userWithRole('shop-owner');
        $subscription = $this->subscriptionForBusiness();

        $response = $this->actingAs($owner)
            ->postJson("/api/v1/subscriptions/{$subscription->id}/cancel");

        // The gate itself must let the owner tier through. (The endpoint's
        // pre-existing int-typed $performedBy vs uuid user id may still 500 —
        // out of scope for this gate fix.)
        $this->assertNotSame(403, $response->status());
    }

    // ------------------------------------------------------------------
    // F-04 — supplier-invoice product_id pinned to the caller's tenant
    // (Low, CWE-639 — documented PR #34 follow-up)
    // ------------------------------------------------------------------

    public function test_supplier_invoice_rejects_foreign_product_id(): void
    {
        $admin = $this->userWithRole('admin');
        $foreignProduct = Product::factory()->create([
            'business_id' => Business::factory()->create()->id,
        ]);

        $this->actingAs($admin)->postJson('/api/v1/supplier-invoices', [
            'items' => [[
                'product_id' => $foreignProduct->id,
                'description' => 'cross-tenant probe',
                'quantity' => 1,
                'unit_price' => 10,
            ]],
        ])->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // F-05 — platform reads are gated (Low, CWE-200)
    // ------------------------------------------------------------------

    public function test_tenant_admin_cannot_read_plan_usage(): void
    {
        $admin = $this->userWithRole('admin');
        $plan = Plan::factory()->create();

        $this->actingAs($admin)
            ->getJson("/admin/plans/{$plan->id}/usage")
            ->assertForbidden();
    }

    public function test_tenant_admin_cannot_calculate_proration(): void
    {
        $admin = $this->userWithRole('admin');
        $plan = Plan::factory()->create();

        $this->actingAs($admin)
            ->postJson('/admin/plans/calculate-proration', [
                'current_plan_id' => $plan->id,
                'new_plan_id' => $plan->id,
            ])
            ->assertForbidden();
    }

    // ------------------------------------------------------------------
    // F-06 — public payment initiation carries a throttle (Low, CWE-770)
    // ------------------------------------------------------------------

    public function test_payment_initiation_routes_are_throttled(): void
    {
        $routes = [
            app('router')->getRoutes()->match(Request::create('/payments-gateways/1/2', 'GET')),
            app('router')->getRoutes()->match(Request::create('/payments/1/2', 'POST')),
        ];

        foreach ($routes as $route) {
            $this->assertStringContainsString(
                'throttle',
                implode(',', $route->gatherMiddleware()),
                "Route [{$route->uri()}] must carry a throttle middleware."
            );
        }
    }

    // ------------------------------------------------------------------
    // F-07 — SqlDate rejects unsafe format/column strings (Info, CWE-89)
    // ------------------------------------------------------------------

    public function test_sql_date_rejects_injection_in_format(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SqlDate::format('sale_date', "%' OR 1=1--");
    }

    public function test_sql_date_rejects_injection_in_column(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SqlDate::format('sale_date; DROP TABLE sales', '%Y-%m-%d');
    }
}
