<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::factory()->create();
        $this->business = Business::factory()->create([
            'business_category_id' => $category->id,
        ]);

        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'role' => 'shop-owner',
        ]);

        // Seed permissions
        $perms = ['products-view', 'products-create', 'products-edit', 'products-delete'];
        foreach ($perms as $p) {
            \Spatie\Permission\Models\Permission::create(['name' => $p, 'guard_name' => 'web']);
        }
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $role = \Spatie\Permission\Models\Role::create(['name' => 'shop-owner', 'guard_name' => 'web']);
        $role->syncPermissions($perms);
        $this->owner->assignRole('shop-owner');
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($this->owner, 'web');
    }

    /**
     * Create a product belonging to this business.
     */
    protected function createProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'business_id' => $this->business->id,
            'productName' => 'Test Product ' . rand(1, 9999),
            'internalCode' => 'TP-' . rand(1000, 9999),
            'sales_price' => 100,
            'purchase_price' => 80,
            'status' => 1,
            'active' => 1,
        ], $overrides));
    }

    // =============================================
    // Happy Path Tests
    // =============================================

    public function test_bulk_delete_with_valid_ids(): void
    {
        $p1 = $this->createProduct();
        $p2 = $this->createProduct();
        $p3 = $this->createProduct();

        $response = $this->postJson(route('admin.items.bulk-delete'), [
            'ids' => [$p1->id, $p2->id],
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'success' => true,
        ]);

        // Verify deleted products are soft-deleted
        $this->assertSoftDeleted('products', ['id' => $p1->id]);
        $this->assertSoftDeleted('products', ['id' => $p2->id]);
        // Third product still exists
        $this->assertDatabaseHas('products', ['id' => $p3->id, 'deleted_at' => null]);
    }

    public function test_bulk_delete_single_product(): void
    {
        $product = $this->createProduct();

        $response = $this->postJson(route('admin.items.bulk-delete'), [
            'ids' => [$product->id],
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'success' => true,
        ]);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_bulk_delete_all_products(): void
    {
        $products = collect();
        for ($i = 0; $i < 5; $i++) {
            $products->push($this->createProduct());
        }

        $response = $this->postJson(route('admin.items.bulk-delete'), [
            'ids' => $products->pluck('id')->toArray(),
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'success' => true,
        ]);

        foreach ($products as $product) {
            $this->assertSoftDeleted('products', ['id' => $product->id]);
        }
    }

    // =============================================
    // Validation Tests
    // =============================================

    public function test_bulk_delete_with_empty_array_fails(): void
    {
        $response = $this->postJson(route('admin.items.bulk-delete'), [
            'ids' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ids']);
    }

    public function test_bulk_delete_without_ids_fails(): void
    {
        $response = $this->postJson(route('admin.items.bulk-delete'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ids']);
    }

    public function test_bulk_delete_with_invalid_ids_fails(): void
    {
        $response = $this->postJson(route('admin.items.bulk-delete'), [
            'ids' => [99999, 99998],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ids.0', 'ids.1']);
    }

    public function test_bulk_delete_with_mixed_valid_invalid_ids_fails(): void
    {
        $product = $this->createProduct();

        $response = $this->postJson(route('admin.items.bulk-delete'), [
            'ids' => [$product->id, 99999],
        ]);

        // Validation fails because 99999 doesn't exist
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ids.1']);

        // Original product still exists (validation prevented partial delete)
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_bulk_delete_with_string_ids_fails(): void
    {
        $response = $this->postJson(route('admin.items.bulk-delete'), [
            'ids' => ['abc', 'def'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ids.0', 'ids.1']);
    }

    // =============================================
    // Authorization Tests
    // =============================================

    public function test_bulk_delete_cross_business_products_only_deletes_own(): void
    {
        $ownProduct = $this->createProduct();

        // Create product in another business
        $otherBusiness = Business::factory()->create([
            'business_category_id' => BusinessCategory::factory()->create()->id,
        ]);
        $otherProduct = Product::create([
            'business_id' => $otherBusiness->id,
            'productName' => 'Other Product',
            'internalCode' => 'OP-' . rand(1000, 9999),
            'sales_price' => 100,
            'purchase_price' => 80,
            'status' => 1,
            'active' => 1,
        ]);

        $response = $this->postJson(route('admin.items.bulk-delete'), [
            'ids' => [$ownProduct->id, $otherProduct->id],
        ]);

        $response->assertOk();
        // Only own product soft-deleted, other product remains
        $this->assertSoftDeleted('products', ['id' => $ownProduct->id]);
        $this->assertDatabaseHas('products', ['id' => $otherProduct->id, 'deleted_at' => null]);
    }

    public function test_unauthenticated_user_cannot_bulk_delete(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->postJson(route('admin.items.bulk-delete'), [
            'ids' => [1],
        ]);

        // Unauthenticated gets redirect to login (302) not 403
        $response->assertStatus(302);
    }

    // =============================================
    // Response Format Tests
    // =============================================

    public function test_bulk_delete_response_has_correct_structure(): void
    {
        $product = $this->createProduct();

        $response = $this->postJson(route('admin.items.bulk-delete'), [
            'ids' => [$product->id],
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'deleted',
            'archived',
            'errors',
        ]);
    }

    public function test_bulk_delete_counts_are_accurate(): void
    {
        // Create 3 products
        $products = collect();
        for ($i = 0; $i < 3; $i++) {
            $products->push($this->createProduct());
        }

        $response = $this->postJson(route('admin.items.bulk-delete'), [
            'ids' => $products->pluck('id')->toArray(),
        ]);

        $response->assertOk();
        $json = $response->json();

        $this->assertTrue($json['success']);
        $this->assertIsInt($json['deleted']);
        $this->assertIsInt($json['archived']);
        $this->assertIsInt($json['errors']);
        $this->assertEquals(3, $json['deleted'] + $json['archived'] + $json['errors']);
    }
}
