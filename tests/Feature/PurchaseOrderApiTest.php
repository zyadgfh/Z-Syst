<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Supplier;
use App\Models\Branch;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
    }

    public function test_can_list_purchase_orders(): void
    {
        PurchaseOrder::factory()->count(3)->create(['company_id' => $this->user->company_id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/purchase-orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'po_number', 'status']
                ]
            ]);
    }

    public function test_can_create_purchase_order(): void
    {
        $supplier = Supplier::factory()->create(['company_id' => $this->user->company_id]);
        $branch = Branch::factory()->create(['company_id' => $this->user->company_id]);
        $product = Product::factory()->create(['company_id' => $this->user->company_id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/purchase-orders', [
                'supplier_id' => $supplier->id,
                'branch_id' => $branch->id,
                'notes' => 'Test PO',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity_ordered' => 10,
                        'unit_cost' => 50.00,
                    ]
                ]
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('supplier_id', $supplier->id)
            ->assertJsonPath('status', 'draft');
    }

    public function test_can_approve_purchase_order(): void
    {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->user->company_id,
            'status' => 'draft'
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/purchase-orders/{$purchaseOrder->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'approved');
    }

    public function test_can_cancel_purchase_order(): void
    {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->user->company_id,
            'status' => 'draft'
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/purchase-orders/{$purchaseOrder->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'cancelled');
    }
}