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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
    }

    private function createPoWithItem(string $status): PurchaseOrder
    {
        $supplier = Supplier::factory()->create(['company_id' => $this->company->id]);
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $product = Product::factory()->create(['company_id' => $this->company->id]);

        $po = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'status' => $status,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 0,
            'unit_cost' => 5.00,
            'discount' => 0,
            'tax' => 0,
            'total' => 50.00,
        ]);

        return $po;
    }

    public function test_draft_can_transition_to_pending(): void
    {
        $po = $this->createPoWithItem(PurchaseOrder::STATUS_DRAFT);
        $po->validateStatusTransition(PurchaseOrder::STATUS_PENDING);
        $this->assertTrue(true);
    }

    public function test_draft_can_transition_to_cancelled(): void
    {
        $po = $this->createPoWithItem(PurchaseOrder::STATUS_DRAFT);
        $po->validateStatusTransition(PurchaseOrder::STATUS_CANCELLED);
        $this->assertTrue(true);
    }

    public function test_pending_can_transition_to_approved(): void
    {
        $po = $this->createPoWithItem(PurchaseOrder::STATUS_PENDING);
        $po->validateStatusTransition(PurchaseOrder::STATUS_APPROVED);
        $this->assertTrue(true);
    }

    public function test_approved_can_transition_to_sent(): void
    {
        $po = $this->createPoWithItem(PurchaseOrder::STATUS_APPROVED);
        $po->validateStatusTransition(PurchaseOrder::STATUS_SENT);
        $this->assertTrue(true);
    }

    public function test_sent_can_transition_to_partial(): void
    {
        $po = $this->createPoWithItem(PurchaseOrder::STATUS_SENT);
        $po->validateStatusTransition(PurchaseOrder::STATUS_PARTIAL);
        $this->assertTrue(true);
    }

    public function test_sent_can_transition_to_received(): void
    {
        $po = $this->createPoWithItem(PurchaseOrder::STATUS_SENT);
        $po->validateStatusTransition(PurchaseOrder::STATUS_RECEIVED);
        $this->assertTrue(true);
    }

    public function test_partial_can_transition_to_received(): void
    {
        $po = $this->createPoWithItem(PurchaseOrder::STATUS_PARTIAL);
        $po->validateStatusTransition(PurchaseOrder::STATUS_RECEIVED);
        $this->assertTrue(true);
    }

    public function test_received_cannot_transition_back_to_draft(): void
    {
        $po = $this->createPoWithItem(PurchaseOrder::STATUS_RECEIVED);

        $this->expectException(\InvalidArgumentException::class);
        $po->validateStatusTransition(PurchaseOrder::STATUS_DRAFT);
    }

    public function test_cancelled_cannot_transition_to_any_status(): void
    {
        $po = $this->createPoWithItem(PurchaseOrder::STATUS_CANCELLED);

        $this->expectException(\InvalidArgumentException::class);
        $po->validateStatusTransition(PurchaseOrder::STATUS_APPROVED);
    }

    public function test_received_cannot_transition_to_approved(): void
    {
        $po = $this->createPoWithItem(PurchaseOrder::STATUS_RECEIVED);

        $this->expectException(\InvalidArgumentException::class);
        $po->validateStatusTransition(PurchaseOrder::STATUS_APPROVED);
    }

    public function test_status_constants_are_correct(): void
    {
        $this->assertEquals('draft', PurchaseOrder::STATUS_DRAFT);
        $this->assertEquals('pending', PurchaseOrder::STATUS_PENDING);
        $this->assertEquals('approved', PurchaseOrder::STATUS_APPROVED);
        $this->assertEquals('sent', PurchaseOrder::STATUS_SENT);
        $this->assertEquals('received', PurchaseOrder::STATUS_RECEIVED);
        $this->assertEquals('partial', PurchaseOrder::STATUS_PARTIAL);
        $this->assertEquals('cancelled', PurchaseOrder::STATUS_CANCELLED);

        $this->assertCount(7, PurchaseOrder::STATUSES);
        $this->assertContains('draft', PurchaseOrder::STATUSES);
        $this->assertContains('received', PurchaseOrder::STATUSES);
    }

    public function test_cannot_edit_non_editable_status(): void
    {
        $po = $this->createPoWithItem(PurchaseOrder::STATUS_APPROVED);
        $this->assertFalse($po->canEdit());

        $po2 = $this->createPoWithItem(PurchaseOrder::STATUS_RECEIVED);
        $this->assertFalse($po2->canEdit());

        $po3 = $this->createPoWithItem(PurchaseOrder::STATUS_DRAFT);
        $this->assertTrue($po3->canEdit());
    }
}
