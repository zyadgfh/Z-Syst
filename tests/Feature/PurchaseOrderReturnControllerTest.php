<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderReturn;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderReturnControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_purchase_order_return_and_decrement_received_quantity(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'status' => 'received',
        ]);

        $purchaseOrderItem = PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 10,
            'unit_cost' => 5.00,
            'discount' => 0,
            'tax' => 0,
            'total' => 50.00,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/admin/purchase-order-returns', [
                'purchase_order_id' => $purchaseOrder->id,
                'supplier_id' => $supplier->id,
                'branch_id' => $branch->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity_returned' => 4,
                        'unit_cost' => 5.00,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.purchase_order_id', $purchaseOrder->id)
            ->assertJsonPath('data.items.0.quantity_returned', '4.00');

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'status' => 'received',
        ]);

        $this->assertDatabaseHas('purchase_order_return_items', [
            'product_id' => $product->id,
            'quantity_returned' => 4,
            'unit_cost' => 5.00,
        ]);

        $this->assertDatabaseHas('purchase_order_items', [
            'id' => $purchaseOrderItem->id,
            'quantity_received' => 6,
        ]);
    }

    public function test_can_list_show_and_delete_purchase_order_returns(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'status' => 'received',
        ]);

        $purchaseOrderReturn = PurchaseOrderReturn::create([
            'company_id' => $company->id,
            'purchase_order_id' => $purchaseOrder->id,
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'return_number' => 'POR-TEST1234',
            'notes' => 'Test return',
            'created_by' => $user->id,
        ]);

        $purchaseOrderReturn->items()->create([
            'product_id' => $product->id,
            'quantity_returned' => 2,
            'unit_cost' => 5.00,
            'total' => 10.00,
        ]);

        $indexResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/purchase-order-returns');

        $indexResponse->assertStatus(200)
            ->assertJsonPath('data.data.0.id', $purchaseOrderReturn->id);

        $showResponse = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/admin/purchase-order-returns/{$purchaseOrderReturn->id}");

        $showResponse->assertStatus(200)
            ->assertJsonPath('data.id', $purchaseOrderReturn->id)
            ->assertJsonPath('data.items.0.quantity_returned', '2.00');

        $deleteResponse = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/admin/purchase-order-returns/{$purchaseOrderReturn->id}");

        $deleteResponse->assertStatus(200)
            ->assertJsonPath('message', 'Purchase order return deleted successfully');

        $this->assertSoftDeleted('purchase_order_returns', ['id' => $purchaseOrderReturn->id]);
    }

    public function test_cannot_return_more_than_received_quantity(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'status' => 'received',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 3,
            'unit_cost' => 5.00,
            'discount' => 0,
            'tax' => 0,
            'total' => 15.00,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/admin/purchase-order-returns', [
                'purchase_order_id' => $purchaseOrder->id,
                'supplier_id' => $supplier->id,
                'branch_id' => $branch->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity_returned' => 5,
                        'unit_cost' => 5.00,
                    ],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity_returned']);
    }
}
