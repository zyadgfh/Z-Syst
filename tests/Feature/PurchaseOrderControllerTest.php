<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Branch $branch;

    protected Supplier $supplier;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
        ]);

        $this->branch = Branch::factory()->create([
            'company_id' => $company->id,
        ]);

        $this->supplier = Supplier::factory()->create([
            'company_id' => $company->id,
        ]);

        $this->product = Product::factory()->create([
            'company_id' => $company->id,
        ]);
    }

    public function test_can_list_purchase_orders()
    {
        PurchaseOrder::factory()->count(3)->create([
            'company_id' => $this->user->company_id,
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/admin/purchase-orders');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.data');
    }

    public function test_can_create_purchase_order()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/admin/purchase-orders', [
                'supplier_id' => $this->supplier->id,
                'branch_id' => $this->branch->id,
                'expected_delivery_date' => now()->addDays(7)->toDateString(),
                'notes' => 'Test purchase order',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity_ordered' => 10,
                        'unit_cost' => 5.50,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.supplier_id', $this->supplier->id)
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $this->supplier->id,
            'company_id' => $this->user->company_id,
        ]);

        $this->assertDatabaseHas('purchase_order_items', [
            'product_id' => $this->product->id,
            'quantity_ordered' => 10,
        ]);
    }

    public function test_cannot_create_purchase_order_without_authentication()
    {
        $response = $this->postJson('/api/v1/admin/purchase-orders', [
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity_ordered' => 10,
                    'unit_cost' => 5.50,
                ],
            ],
        ]);

        $response->assertStatus(401);
    }

    public function test_can_show_purchase_order()
    {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->user->company_id,
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/admin/purchase-orders/{$purchaseOrder->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $purchaseOrder->id);
    }

    public function test_cannot_access_purchase_order_from_different_company()
    {
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->user->company_id,
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/v1/admin/purchase-orders/{$purchaseOrder->id}");

        $response->assertStatus(403);
    }

    public function test_can_approve_purchase_order()
    {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->user->company_id,
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/admin/purchase-orders/{$purchaseOrder->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'status' => 'approved',
        ]);
    }

    public function test_can_send_purchase_order()
    {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->user->company_id,
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/admin/purchase-orders/{$purchaseOrder->id}/send");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'sent');
    }

    public function test_can_cancel_purchase_order()
    {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->user->company_id,
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/admin/purchase-orders/{$purchaseOrder->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_can_update_draft_purchase_order()
    {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->user->company_id,
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/admin/purchase-orders/{$purchaseOrder->id}", [
                'notes' => 'Updated notes',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.notes', 'Updated notes');
    }

    public function test_cannot_update_processed_purchase_order()
    {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->user->company_id,
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/admin/purchase-orders/{$purchaseOrder->id}", [
                'notes' => 'Updated notes',
            ]);

        $response->assertStatus(422);
    }

    public function test_can_delete_draft_purchase_order()
    {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->user->company_id,
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/admin/purchase-orders/{$purchaseOrder->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('purchase_orders', [
            'id' => $purchaseOrder->id,
        ]);
    }

    public function test_cannot_delete_processed_purchase_order()
    {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->user->company_id,
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/admin/purchase-orders/{$purchaseOrder->id}");

        $response->assertStatus(422);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
        ]);
    }
}
