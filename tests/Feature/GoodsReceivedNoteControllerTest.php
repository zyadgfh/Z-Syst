<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\GoodsReceivedNote;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Branch;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoodsReceivedNoteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_goods_received_note_and_update_purchase_order_status(): void
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
            'status' => 'approved',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 0,
            'unit_cost' => 10.00,
            'discount' => 0,
            'tax' => 0,
            'total' => 100.00,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/admin/goods-received-notes', [
                'purchase_order_id' => $purchaseOrder->id,
                'supplier_id' => $supplier->id,
                'branch_id' => $branch->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity_received' => 5,
                        'unit_cost' => 10.00,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.purchase_order_id', $purchaseOrder->id)
            ->assertJsonPath('data.supplier_id', $supplier->id);

        $this->assertDatabaseHas('goods_received_notes', [
            'purchase_order_id' => $purchaseOrder->id,
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'company_id' => $company->id,
        ]);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'status' => 'partial',
        ]);
    }
}
