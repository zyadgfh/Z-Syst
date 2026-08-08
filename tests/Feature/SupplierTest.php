<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\SupplierRating;
use App\Models\SupplierContract;
use App\Models\SupplierPerformance;
use App\Models\User;
use App\Services\SupplierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected SupplierService $supplierService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->supplierService = app(SupplierService::class);
    }

    /**
     * Test creating a supplier.
     */
    public function test_can_create_supplier(): void
    {
        $user = User::factory()->create();

        $data = [
            'business_id' => $user->business_id,
            'branch_id' => $user->branch_id,
            'company_name' => 'Test Pharmacy Supplies',
            'contact_person' => 'John Doe',
            'email' => 'john@test.com',
            'phone' => '+1234567890',
            'payment_terms' => 'net_30',
            'credit_limit' => 50000.00,
            'is_active' => true,
        ];

        $supplier = $this->supplierService->create($data);

        $this->assertInstanceOf(Supplier::class, $supplier);
        $this->assertEquals('Test Pharmacy Supplies', $supplier->company_name);
        $this->assertEquals('John Doe', $supplier->contact_person);
        $this->assertTrue($supplier->is_active);
    }

    /**
     * Test updating a supplier.
     */
    public function test_can_update_supplier(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create(['business_id' => $user->business_id]);

        $data = [
            'company_name' => 'Updated Pharmacy Supplies',
            'contact_person' => 'Jane Doe',
        ];

        $updatedSupplier = $this->supplierService->update($supplier, $data);

        $this->assertEquals('Updated Pharmacy Supplies', $updatedSupplier->company_name);
        $this->assertEquals('Jane Doe', $updatedSupplier->contact_person);
    }

    /**
     * Test adding a rating to supplier.
     */
    public function test_can_add_rating(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create(['business_id' => $user->business_id]);

        $rating = $this->supplierService->addRating($supplier, [
            'rating' => 4.5,
            'category' => 'quality',
            'review' => 'Great quality products',
            'rated_by' => $user->id,
        ]);

        $this->assertInstanceOf(SupplierRating::class, $rating);
        $this->assertEquals(4.5, $rating->rating);
        $this->assertEquals('quality', $rating->category);
    }

    /**
     * Test calculating supplier performance.
     */
    public function test_can_calculate_performance(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create(['business_id' => $user->business_id]);

        $performance = $this->supplierService->calculatePerformance($supplier);

        $this->assertInstanceOf(SupplierPerformance::class, $performance);
        $this->assertEquals($supplier->id, $performance->supplier_id);
        $this->assertNotNull($performance->calculated_at);
    }

    /**
     * Test supplier business scope.
     */
    public function test_supplier_business_scope(): void
    {
        $business1 = User::factory()->create()->business_id;
        $business2 = User::factory()->create()->business_id;

        $supplier1 = Supplier::factory()->create(['business_id' => $business1]);
        $supplier2 = Supplier::factory()->create(['business_id' => $business2]);

        $business1Suppliers = Supplier::forBusiness($business1)->get();

        $this->assertCount(1, $business1Suppliers);
        $this->assertEquals($supplier1->id, $business1Suppliers->first()->id);
    }

    /**
     * Test supplier active scope.
     */
    public function test_supplier_active_scope(): void
    {
        $supplier1 = Supplier::factory()->create(['is_active' => true]);
        $supplier2 = Supplier::factory()->create(['is_active' => false]);

        $activeSuppliers = Supplier::active()->get();

        $this->assertCount(1, $activeSuppliers);
        $this->assertEquals($supplier1->id, $activeSuppliers->first()->id);
    }

    /**
     * Test supplier performance score calculation.
     */
    public function test_supplier_performance_score_calculation(): void
    {
        $supplier = Supplier::factory()->create();

        SupplierPerformance::factory()->create([
            'supplier_id' => $supplier->id,
            'on_time_delivery_rate' => 95.0,
            'quality_score' => 90.0,
            'price_competitiveness' => 85.0,
            'responsiveness' => 88.0,
        ]);

        $supplier->calculatePerformanceScore();

        $expectedScore = (95.0 * 0.4) + (90.0 * 0.3) + (85.0 * 0.2) + (88.0 * 0.1);
        $this->assertEquals($expectedScore, $supplier->performance_score);
    }

    /**
     * Test supplier average rating.
     */
    public function test_supplier_average_rating(): void
    {
        $supplier = Supplier::factory()->create();

        SupplierRating::factory()->create(['supplier_id' => $supplier->id, 'rating' => 4.0]);
        SupplierRating::factory()->create(['supplier_id' => $supplier->id, 'rating' => 5.0]);

        $this->assertEquals(4.5, $supplier->average_rating);
    }

    /**
     * Test API endpoint for creating supplier.
     */
    public function test_api_can_create_supplier(): void
    {
        $user = User::factory()->create();

        $data = [
            'company_name' => 'Test Supplier',
            'contact_person' => 'Test Contact',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/suppliers', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Supplier created successfully',
            ]);
    }

    /**
     * Test API endpoint for listing suppliers.
     */
    public function test_api_can_list_suppliers(): void
    {
        $user = User::factory()->create();
        Supplier::factory()->count(5)->create(['business_id' => $user->business_id]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/suppliers');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /**
     * Test API endpoint for showing supplier.
     */
    public function test_api_can_show_supplier(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create(['business_id' => $user->business_id]);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/v1/suppliers/{$supplier->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test API endpoint for updating supplier.
     */
    public function test_api_can_update_supplier(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create(['business_id' => $user->business_id]);

        $data = [
            'company_name' => 'Updated Name',
            'contact_person' => 'Updated Contact',
        ];

        $response = $this->actingAs($user, 'api')
            ->putJson("/api/v1/suppliers/{$supplier->id}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Supplier updated successfully',
            ]);
    }

    /**
     * Test API endpoint for deleting supplier.
     */
    public function test_api_can_delete_supplier(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create(['business_id' => $user->business_id]);

        $response = $this->actingAs($user, 'api')
            ->deleteJson("/api/v1/suppliers/{$supplier->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Supplier deleted successfully',
            ]);

        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    /**
     * Test API endpoint for calculating performance.
     */
    public function test_api_can_calculate_performance(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create(['business_id' => $user->business_id]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/v1/suppliers/{$supplier->id}/calculate-performance");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Performance calculated successfully',
            ]);
    }

    /**
     * Test API endpoint for top performers.
     */
    public function test_api_can_get_top_performers(): void
    {
        $user = User::factory()->create();
        Supplier::factory()->count(10)->create(['business_id' => $user->business_id, 'performance_score' => 85]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/suppliers/top-performers');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }
}
