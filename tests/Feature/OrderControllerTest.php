<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Branch $branch;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
        ]);

        $this->branch = Branch::factory()->create([
            'company_id' => $company->id,
        ]);

        $this->product = Product::factory()->create([
            'company_id' => $company->id,
        ]);
    }

    public function test_can_list_orders()
    {
        Order::factory()->count(3)->create([
            'company_id' => $this->user->company_id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/admin/orders');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_order()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/admin/orders', [
                'branch_id' => $this->branch->id,
                'customer_name' => 'John Doe',
                'customer_phone' => '1234567890',
                'notes' => 'Test order',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                        'price' => 10.50,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.customer_name', 'John Doe')
            ->assertJsonPath('data.total', 21);

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'John Doe',
            'company_id' => $this->user->company_id,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);
    }

    public function test_cannot_create_order_without_authentication()
    {
        $response = $this->postJson('/api/v1/admin/orders', [
            'branch_id' => $this->branch->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'price' => 10.50,
                ],
            ],
        ]);

        $response->assertStatus(401);
    }

    public function test_can_show_order()
    {
        $order = Order::factory()->create([
            'company_id' => $this->user->company_id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/admin/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order->id);
    }

    public function test_cannot_access_order_from_different_company()
    {
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $order = Order::factory()->create([
            'company_id' => $this->user->company_id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/v1/admin/orders/{$order->id}");

        $response->assertStatus(404);
    }

    public function test_can_update_pending_order()
    {
        $order = Order::factory()->create([
            'company_id' => $this->user->company_id,
            'branch_id' => $this->branch->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/admin/orders/{$order->id}", [
                'customer_name' => 'Updated Name',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.customer_name', 'Updated Name');
    }

    public function test_cannot_update_processed_order()
    {
        $order = Order::factory()->create([
            'company_id' => $this->user->company_id,
            'branch_id' => $this->branch->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/admin/orders/{$order->id}", [
                'customer_name' => 'Updated Name',
            ]);

        $response->assertStatus(422);
    }

    public function test_can_delete_pending_order()
    {
        $order = Order::factory()->create([
            'company_id' => $this->user->company_id,
            'branch_id' => $this->branch->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/admin/orders/{$order->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('orders', [
            'id' => $order->id,
        ]);
    }

    public function test_cannot_delete_processed_order()
    {
        $order = Order::factory()->create([
            'company_id' => $this->user->company_id,
            'branch_id' => $this->branch->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/admin/orders/{$order->id}");

        $response->assertStatus(422);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
        ]);
    }
}
