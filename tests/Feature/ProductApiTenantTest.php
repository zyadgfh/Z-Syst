<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_endpoint_is_tenant_scoped(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['company_id' => $user->company_id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $product->id]);
    }
}
