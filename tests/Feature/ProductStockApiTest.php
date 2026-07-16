<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStockApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_and_list_stock_for_current_tenant(): void
    {
        $company = Company::factory()->create(['slug' => 'demo']);
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/product-stocks', [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 25,
            'reorder_level' => 5,
            'reorder_quantity' => 10,
            'batch_number' => 'BATCH-001',
            'expiry_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $response->assertStatus(201);

        $listResponse = $this->getJson('/api/v1/product-stocks');
        $listResponse->assertStatus(200);
        $this->assertDatabaseHas('product_stocks', ['product_id' => $product->id, 'company_id' => $company->id]);
    }
}
