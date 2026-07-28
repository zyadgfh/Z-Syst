<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderReturn;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderReturnApiTest extends TestCase
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

    public function test_can_list_purchase_order_returns(): void
    {
        $supplier = Supplier::factory()->create(['company_id' => $this->company->id]);
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $po = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'status' => 'received',
        ]);

        PurchaseOrderReturn::create([
            'company_id' => $this->company->id,
            'purchase_order_id' => $po->id,
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'return_number' => 'POR-TEST001',
            'notes' => 'Test return',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/purchase-order-returns');

        $response->assertStatus(200)
            ->assertJsonPath('data.data.0.return_number', 'POR-TEST001');
    }

    public function test_can_show_purchase_order_return(): void
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

        $return = PurchaseOrderReturn::create([
            'company_id' => $this->company->id,
            'purchase_order_id' => $po->id,
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'return_number' => 'POR-TEST002',
            'notes' => 'Test return',
            'created_by' => $this->user->id,
        ]);

        $return->items()->create([
            'product_id' => $product->id,
            'quantity_returned' => 5,
            'unit_cost' => 10.00,
            'total' => 50.00,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/purchase-order-returns/{$return->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $return->id)
            ->assertJsonPath('data.total_amount', 50.0)
            ->assertJsonPath('data.items.0.quantity_returned', '5.00');
    }

    public function test_can_update_purchase_order_return(): void
    {
        $supplier = Supplier::factory()->create(['company_id' => $this->company->id]);
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $po = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'status' => 'received',
        ]);

        $return = PurchaseOrderReturn::create([
            'company_id' => $this->company->id,
            'purchase_order_id' => $po->id,
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'return_number' => 'POR-TEST003',
            'notes' => 'Original notes',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/purchase-order-returns/{$return->id}", [
                'notes' => 'Updated notes',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.notes', 'Updated notes');

        $this->assertDatabaseHas('purchase_order_returns', [
            'id' => $return->id,
            'notes' => 'Updated notes',
        ]);
    }

    public function test_can_delete_purchase_order_return(): void
    {
        $supplier = Supplier::factory()->create(['company_id' => $this->company->id]);
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $po = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'status' => 'received',
        ]);

        $return = PurchaseOrderReturn::create([
            'company_id' => $this->company->id,
            'purchase_order_id' => $po->id,
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'return_number' => 'POR-TEST004',
            'notes' => 'Test return',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/purchase-order-returns/{$return->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Purchase order return deleted successfully');

        $this->assertSoftDeleted('purchase_order_returns', ['id' => $return->id]);
    }

    public function test_cannot_access_other_company_return(): void
    {
        $otherCompany = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $otherCompany->id]);
        $branch = Branch::factory()->create(['company_id' => $otherCompany->id]);
        $po = PurchaseOrder::factory()->create([
            'company_id' => $otherCompany->id,
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'status' => 'received',
        ]);

        $return = PurchaseOrderReturn::create([
            'company_id' => $otherCompany->id,
            'purchase_order_id' => $po->id,
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'return_number' => 'POR-OTHER001',
            'notes' => 'Other company return',
            'created_by' => User::factory()->create(['company_id' => $otherCompany->id])->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/purchase-order-returns/{$return->id}");

        $response->assertStatus(403);
    }

    public function test_purchase_order_return_model_helpers(): void
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

        $return = PurchaseOrderReturn::create([
            'company_id' => $this->company->id,
            'purchase_order_id' => $po->id,
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'return_number' => 'POR-TEST005',
            'notes' => 'Test return',
            'created_by' => $this->user->id,
        ]);

        $return->items()->create([
            'product_id' => $product->id,
            'quantity_returned' => 5,
            'unit_cost' => 10.00,
            'total' => 50.00,
        ]);

        $return->load('items');

        $this->assertEquals(50.0, $return->totalAmount());
        $this->assertEquals(5.0, $return->totalQuantityReturned());
        $this->assertTrue($return->canEdit());
    }
}
