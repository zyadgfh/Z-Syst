<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private PurchaseOrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->service = app(PurchaseOrderService::class);
    }

    public function test_update_purchase_order_with_new_items(): void
    {
        $supplier = Supplier::factory()->create(['company_id' => $this->company->id]);
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $product1 = Product::factory()->create(['company_id' => $this->company->id]);
        $product2 = Product::factory()->create(['company_id' => $this->company->id]);

        $po = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'status' => 'draft',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product1->id,
            'quantity_ordered' => 10,
            'quantity_received' => 0,
            'unit_cost' => 5.00,
            'discount' => 0,
            'tax' => 0,
            'total' => 50.00,
        ]);

        $updated = $this->service->update($po, [
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
        ], [
            [
                'product_id' => $product2->id,
                'quantity_ordered' => 20,
                'unit_cost' => 15.00,
            ],
        ]);

        $this->assertCount(1, $updated->items);
        $this->assertEquals($product2->id, $updated->items->first()->product_id);
        $this->assertEquals(20, $updated->items->first()->quantity_ordered);
        $this->assertEquals(300.00, $updated->total);
    }

    public function test_receive_marks_po_as_received_when_all_items_received(): void
    {
        $supplier = Supplier::factory()->create(['company_id' => $this->company->id]);
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $product = Product::factory()->create(['company_id' => $this->company->id]);

        $po = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'status' => 'sent',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 10,
            'unit_cost' => 5.00,
            'discount' => 0,
            'tax' => 0,
            'total' => 50.00,
        ]);

        $result = $this->service->receive($po);

        $this->assertEquals('received', $result->status);
    }

    public function test_receive_marks_po_as_partial_when_some_items_pending(): void
    {
        $supplier = Supplier::factory()->create(['company_id' => $this->company->id]);
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $product = Product::factory()->create(['company_id' => $this->company->id]);

        $po = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'status' => 'sent',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 5,
            'unit_cost' => 5.00,
            'discount' => 0,
            'tax' => 0,
            'total' => 50.00,
        ]);

        $result = $this->service->receive($po);

        $this->assertEquals('partial', $result->status);
    }

    public function test_duplicate_creates_new_draft_po_with_items(): void
    {
        $supplier = Supplier::factory()->create(['company_id' => $this->company->id]);
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $product = Product::factory()->create(['company_id' => $this->company->id]);

        $po = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'status' => 'received',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 10,
            'unit_cost' => 5.00,
            'discount' => 0,
            'tax' => 0,
            'total' => 50.00,
        ]);

        $duplicate = $this->service->duplicate($po, $this->user->id);

        $this->assertNotEquals($po->id, $duplicate->id);
        $this->assertEquals('draft', $duplicate->status);
        $this->assertEquals($supplier->id, $duplicate->supplier_id);
        $this->assertCount(1, $duplicate->items);
        $this->assertEquals(0, $duplicate->items->first()->quantity_received);
        $this->assertEquals(10, $duplicate->items->first()->quantity_ordered);
    }

    public function test_purchase_order_model_helper_methods(): void
    {
        $supplier = Supplier::factory()->create(['company_id' => $this->company->id]);
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $product = Product::factory()->create(['company_id' => $this->company->id]);

        $po = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'status' => 'draft',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 3,
            'unit_cost' => 5.00,
            'discount' => 0,
            'tax' => 0,
            'total' => 50.00,
        ]);

        $po->load('items');

        $this->assertTrue($po->canEdit());
        $this->assertFalse($po->isFullyReceived());
        $this->assertFalse($po->isPartiallyReceived());
        $this->assertEquals(10, $po->totalOrdered());
        $this->assertEquals(3, $po->totalReceived());
        $this->assertEquals(7, $po->totalPending());
        $this->assertEquals(30.0, $po->receptionPercentage());
    }

    public function test_status_transition_validation(): void
    {
        $po = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'received',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $po->validateStatusTransition('draft');
    }

    public function test_valid_status_transition_does_not_throw(): void
    {
        $po = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'draft',
        ]);

        $po->validateStatusTransition('pending');
        $po->validateStatusTransition('cancelled');

        $this->assertTrue(true);
    }
}
