<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SaleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_with_sales_permission_can_create_sale(): void
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        $product = Product::create([
            'company_id' => $company->id,
            'product_category_id' => null,
            'sku' => 'TEST-'.now()->timestamp,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'A test product for sales API.',
            'cost_price' => 5.00,
            'retail_price' => 10.00,
            'track_inventory' => true,
            'is_active' => true,
        ]);

        $stock = ProductStock::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 10,
            'reorder_level' => 5,
            'reorder_quantity' => 10,
            'batch_number' => 'BATCH-TEST-001',
            'expiry_date' => null,
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'Create Sale',
            'slug' => 'sales.create',
            'description' => 'Permission to create sales',
            'module' => 'Sales',
            'group' => 'Operations',
            'sort_order' => 1,
            'status' => true,
        ]);

        $role = Role::create([
            'name' => 'Cashier',
            'slug' => 'cashier',
            'description' => 'Cashier role for sales operations',
            'color_badge' => '#34D399',
            'priority' => 50,
            'is_system' => false,
            'status' => true,
        ]);

        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'role' => 'cashier',
        ]);

        $user->assignRole($role);

        Sanctum::actingAs($user, [], 'sanctum');

        $payload = [
            'branch_id' => $branch->id,
            'company_id' => $company->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 10.00,
                    'discount' => 0,
                    'tax' => 0,
                    'batch_number' => 'BATCH-TEST-001',
                ],
            ],
            'payment_method' => 'cash',
            'amount_paid' => 20.00,
        ];

        $response = $this->postJson('/api/v1/sales', $payload);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'invoice_number',
                    'branch_id',
                    'company_id',
                    'user_id',
                    'subtotal',
                    'total_amount',
                    'amount_paid',
                    'payment_method',
                    'items' => [
                        ['id', 'product_id', 'quantity', 'unit_price', 'total'],
                    ],
                ],
            ])
            ->assertJsonPath('data.branch_id', $branch->id)
            ->assertJsonPath('data.company_id', $company->id)
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.items.0.product_id', $product->id)
            ->assertJsonPath('data.items.0.quantity', 2);

        $this->assertDatabaseHas('sales', [
            'branch_id' => $branch->id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'payment_method' => 'cash',
        ]);

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 10.00,
        ]);

        $this->assertDatabaseHas('product_stocks', [
            'id' => $stock->id,
            'quantity' => 8,
        ]);
    }
}
