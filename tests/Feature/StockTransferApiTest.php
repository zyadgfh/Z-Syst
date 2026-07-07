<?php

namespace Tests\Feature;

use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * StockTransferApiTest
 * 
 * Feature tests for stock transfer API endpoints.
 * Tests authentication, authorization, validation, and complete workflows.
 */
class StockTransferApiTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $fromBranch;
    protected Branch $toBranch;
    protected User $user;
    protected User $adminUser;
    protected Product $product;
    protected ProductStock $productStock;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->company = Company::factory()->create();
        $this->fromBranch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->toBranch = Branch::factory()->create(['company_id' => $this->company->id]);
        
        // Create regular user
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->fromBranch->id,
        ]);
        
        // Create admin user with super admin role
        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->fromBranch->id,
            'role' => 'super_admin',
        ]);
        
        $this->product = Product::factory()->create(['company_id' => $this->company->id]);
        $this->productStock = ProductStock::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'branch_id' => $this->fromBranch->id,
            'quantity' => 100,
        ]);
    }

    /**
     * Test unauthenticated user cannot access stock transfers.
     */
    public function test_unauthenticated_user_cannot_access_stock_transfers(): void
    {
        $response = $this->getJson('/api/v1/admin/stock-transfers');
        $response->assertStatus(401);
    }

    /**
     * Test authenticated user can list stock transfers.
     */
    public function test_authenticated_user_can_list_stock_transfers(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        StockTransfer::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
        ]);

        $response = $this->getJson('/api/v1/admin/stock-transfers');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'transfer_number',
                        'status',
                        'from_branch',
                        'to_branch',
                        'total_items',
                        'total_quantity',
                        'total_value',
                    ],
                ],
            ]);
    }

    /**
     * Test creating a stock transfer.
     */
    public function test_can_create_stock_transfer(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        $data = [
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'notes' => 'Test transfer',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_stock_id' => $this->productStock->id,
                    'quantity_requested' => 50,
                    'batch_number' => 'BATCH001',
                    'expiry_date' => now()->addMonths(6)->format('Y-m-d'),
                    'unit_cost' => 10.00,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/admin/stock-transfers', $data);
        
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'transfer_number',
                    'status',
                    'from_branch',
                    'to_branch',
                    'items',
                ],
            ]);

        $this->assertDatabaseHas('stock_transfers', [
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Test validation when creating stock transfer.
     */
    public function test_validation_when_creating_stock_transfer(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        $data = [
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->fromBranch->id, // Same branch - should fail
            'items' => [],
        ];

        $response = $this->postJson('/api/v1/admin/stock-transfers', $data);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to_branch_id', 'items']);
    }

    /**
     * Test viewing a single stock transfer.
     */
    public function test_can_view_stock_transfer(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        $transfer = StockTransfer::factory()->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
        ]);

        $response = $this->getJson("/api/v1/admin/stock-transfers/{$transfer->id}");
        
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $transfer->id,
                    'transfer_number' => $transfer->transfer_number,
                ],
            ]);
    }

    /**
     * Test approving a stock transfer.
     */
    public function test_can_approve_stock_transfer(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        $transfer = StockTransfer::factory()->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/admin/stock-transfers/{$transfer->id}/approve");
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Stock transfer approved successfully.',
                'data' => [
                    'status' => 'approved',
                ],
            ]);

        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->id,
            'status' => 'approved',
        ]);
    }

    /**
     * Test rejecting a stock transfer.
     */
    public function test_can_reject_stock_transfer(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        $transfer = StockTransfer::factory()->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'status' => 'pending',
        ]);

        $data = [
            'rejection_reason' => 'Insufficient stock availability',
        ];

        $response = $this->postJson("/api/v1/admin/stock-transfers/{$transfer->id}/reject", $data);
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Stock transfer rejected successfully.',
                'data' => [
                    'status' => 'rejected',
                ],
            ]);

        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->id,
            'status' => 'rejected',
            'rejection_reason' => 'Insufficient stock availability',
        ]);
    }

    /**
     * Test shipping a stock transfer.
     */
    public function test_can_ship_stock_transfer(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        $transfer = StockTransfer::factory()->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'status' => 'approved',
        ]);

        $transferItem = StockTransferItem::factory()->create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $this->product->id,
            'product_stock_id' => $this->productStock->id,
            'quantity_requested' => 50,
            'quantity_sent' => 0,
            'unit_cost' => 10.00,
        ]);

        $data = [
            'items' => [
                [
                    'id' => $transferItem->id,
                    'quantity_sent' => 50,
                    'batch_number' => 'BATCH001',
                    'expiry_date' => now()->addMonths(6)->format('Y-m-d'),
                ],
            ],
        ];

        $response = $this->postJson("/api/v1/admin/stock-transfers/{$transfer->id}/ship", $data);
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Stock transfer shipped successfully.',
                'data' => [
                    'status' => 'in_transit',
                ],
            ]);

        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->id,
            'status' => 'in_transit',
        ]);
    }

    /**
     * Test receiving a stock transfer.
     */
    public function test_can_receive_stock_transfer(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        $transfer = StockTransfer::factory()->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'status' => 'in_transit',
        ]);

        $transferItem = StockTransferItem::factory()->create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $this->product->id,
            'quantity_requested' => 50,
            'quantity_sent' => 50,
            'quantity_received' => 0,
            'unit_cost' => 10.00,
            'batch_number' => 'BATCH001',
            'expiry_date' => now()->addMonths(6),
        ]);

        $data = [
            'items' => [
                [
                    'id' => $transferItem->id,
                    'quantity_received' => 50,
                ],
            ],
        ];

        $response = $this->postJson("/api/v1/admin/stock-transfers/{$transfer->id}/receive", $data);
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Stock transfer received successfully.',
                'data' => [
                    'status' => 'received',
                ],
            ]);

        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->id,
            'status' => 'received',
        ]);
    }

    /**
     * Test cancelling a stock transfer.
     */
    public function test_can_cancel_stock_transfer(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        $transfer = StockTransfer::factory()->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'status' => 'pending',
        ]);

        $data = [
            'cancellation_reason' => 'No longer needed',
        ];

        $response = $this->postJson("/api/v1/admin/stock-transfers/{$transfer->id}/cancel", $data);
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Stock transfer cancelled successfully.',
                'data' => [
                    'status' => 'cancelled',
                ],
            ]);

        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->id,
            'status' => 'cancelled',
        ]);
    }

    /**
     * Test getting transfer statistics.
     */
    public function test_can_get_transfer_statistics(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        StockTransfer::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/v1/admin/stock-transfers/statistics');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_transfers',
                    'pending_transfers',
                    'approved_transfers',
                    'in_transit_transfers',
                    'received_transfers',
                    'rejected_transfers',
                    'cancelled_transfers',
                ],
            ]);
    }

    /**
     * Test user cannot access transfers from different company.
     */
    public function test_user_cannot_access_transfers_from_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        Sanctum::actingAs($otherUser, ['*']);

        $transfer = StockTransfer::factory()->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
        ]);

        $response = $this->getJson("/api/v1/admin/stock-transfers/{$transfer->id}");
        $response->assertStatus(403);
    }

    /**
     * Test that approval fails for non-pending transfer.
     */
    public function test_cannot_approve_non_pending_transfer(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        $transfer = StockTransfer::factory()->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'status' => 'approved',
        ]);

        $response = $this->postJson("/api/v1/admin/stock-transfers/{$transfer->id}/approve");
        $response->assertStatus(422);
    }

    /**
     * Test that shipping fails without sufficient stock.
     */
    public function test_cannot_ship_without_sufficient_stock(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        $transfer = StockTransfer::factory()->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'status' => 'approved',
        ]);

        $transferItem = StockTransferItem::factory()->create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $this->product->id,
            'quantity_requested' => 200, // More than available
            'quantity_sent' => 0,
            'unit_cost' => 10.00,
        ]);

        $data = [
            'items' => [
                [
                    'id' => $transferItem->id,
                    'quantity_sent' => 200,
                ],
            ],
        ];

        $response = $this->postJson("/api/v1/admin/stock-transfers/{$transfer->id}/ship", $data);
        $response->assertStatus(422);
    }

    /**
     * Test updating stock transfer notes.
     */
    public function test_can_update_stock_transfer_notes(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        $transfer = StockTransfer::factory()->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'status' => 'pending',
            'notes' => 'Original notes',
        ]);

        $data = [
            'notes' => 'Updated notes',
        ];

        $response = $this->putJson("/api/v1/admin/stock-transfers/{$transfer->id}", $data);
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Stock transfer updated successfully.',
                'data' => [
                    'notes' => 'Updated notes',
                ],
            ]);
    }

    /**
     * Test deleting a stock transfer (soft delete).
     */
    public function test_can_delete_stock_transfer(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        $transfer = StockTransfer::factory()->create([
            'company_id' => $this->company->id,
            'from_branch_id' => $this->fromBranch->id,
            'to_branch_id' => $this->toBranch->id,
            'status' => 'cancelled',
        ]);

        $response = $this->deleteJson("/api/v1/admin/stock-transfers/{$transfer->id}");
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Stock transfer deleted successfully.',
            ]);

        $this->assertSoftDeleted('stock_transfers', [
            'id' => $transfer->id,
        ]);
    }
}
