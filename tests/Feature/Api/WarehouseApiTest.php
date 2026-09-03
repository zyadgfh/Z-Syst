<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
    }

    public function test_can_list_warehouses(): void
    {
        Warehouse::factory()->count(3)->create(['business_id' => $this->business->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/warehouses');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => ['id', 'name', 'code', 'location', 'is_default', 'is_active', 'business_id'],
                ],
            ]);
    }

    public function test_can_create_warehouse(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/warehouses', [
                'name' => 'Main Pharmacy',
                'code' => 'WH-MAIN',
                'location' => 'Downtown Branch',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Main Pharmacy')
            ->assertJsonPath('data.code', 'WH-MAIN');

        $this->assertDatabaseHas('warehouses', [
            'business_id' => $this->business->id,
            'code' => 'WH-MAIN',
        ]);
    }

    public function test_warehouse_code_must_be_unique(): void
    {
        Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'Existing',
            'code' => 'WH-DUP',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/warehouses', [
                'name' => 'New',
                'code' => 'WH-DUP',
            ]);

        $response->assertStatus(422);
    }

    public function test_can_show_warehouse(): void
    {
        $warehouse = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'WH1',
            'code' => 'WH-SHOW',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/warehouses/{$warehouse->id}");

        $response->assertOk()
            ->assertJsonPath('data.name', 'WH1');
    }

    public function test_can_update_warehouse(): void
    {
        $warehouse = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'Old Name',
            'code' => 'WH-UPD',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/warehouses/{$warehouse->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_can_delete_warehouse(): void
    {
        $warehouse = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'To Delete',
            'code' => 'WH-DEL',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/warehouses/{$warehouse->id}");

        $response->assertOk()
            ->assertJsonFragment(['message' => __('Warehouse deleted successfully.')]);

        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id]); /* soft delete */
    }

    public function test_cannot_access_other_business_warehouse(): void
    {
        $otherBusiness = Business::factory()->create();
        $warehouse = Warehouse::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other WH',
            'code' => 'WH-OTHER',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/warehouses/{$warehouse->id}");

        $response->assertForbidden();
    }

    public function test_can_view_warehouse_stock(): void
    {
        $warehouse = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'WH1',
            'code' => 'WH-STK',
        ]);

        $product = Product::factory()->create(['business_id' => $this->business->id]);

        WarehouseStock::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/warehouses/{$warehouse->id}/stock");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'warehouse_id', 'product_id', 'quantity'],
                ],
            ]);
    }
}
