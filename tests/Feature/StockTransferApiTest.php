<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferApiTest extends TestCase
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

    public function test_can_list_stock_transfers(): void
    {
        StockTransfer::factory()->count(3)->create([
            'company_id' => $this->user->company_id,
            'from_branch_id' => Branch::factory()->create(['company_id' => $this->user->company_id])->id,
            'to_branch_id' => Branch::factory()->create(['company_id' => $this->user->company_id])->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/stock-transfers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'status']
                ]
            ]);
    }

    public function test_can_create_stock_transfer(): void
    {
        $fromBranch = Branch::factory()->create(['company_id' => $this->user->company_id]);
        $toBranch = Branch::factory()->create(['company_id' => $this->user->company_id]);
        $product = Product::factory()->create(['company_id' => $this->user->company_id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/stock-transfers', [
                'from_branch_id' => $fromBranch->id,
                'to_branch_id' => $toBranch->id,
                'notes' => 'Test transfer',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity_requested' => 10,
                        'unit_cost' => 25.00,
                    ]
                ]
            ]);

        $response->assertStatus(201);
    }

    public function test_can_get_statistics(): void
    {
        StockTransfer::factory()->count(3)->create([
            'company_id' => $this->user->company_id,
            'from_branch_id' => Branch::factory()->create(['company_id' => $this->user->company_id])->id,
            'to_branch_id' => Branch::factory()->create(['company_id' => $this->user->company_id])->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/stock-transfers/statistics');

        $response->assertStatus(200);
    }
}