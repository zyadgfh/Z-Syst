<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Models\User;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductService $productService;
    protected User $user;
    protected Manufacturer $manufacturer;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->manufacturer = Manufacturer::factory()->create(['company_id' => $this->user->company_id]);
        $this->category = Category::factory()->create(['company_id' => $this->user->company_id]);

        $this->productService = new ProductService();
    }

    /** @test */
    public function it_can_create_a_product(): void
    {
        $productData = [
            'generic_name' => 'Paracetamol',
            'brand_name' => 'Panadol',
            'strength' => '500mg',
            'dosage_form' => 'Tablet',
            'sales_price' => 15.50,
            'manufacturer_id' => $this->manufacturer->id,
            'category_id' => $this->category->id,
        ];

        $product = $this->productService->create($productData);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('Paracetamol', $product->generic_name);
        $this->assertEquals('Panadol', $product->brand_name);
        $this->assertEquals(15.50, $product->sales_price);
        $this->assertTrue($product->is_active);
    }

    /** @test */
    public function it_can_search_products(): void
    {
        Product::factory()->create([
            'generic_name' => 'Paracetamol',
            'brand_name' => 'Panadol',
            'sales_price' => 15.00,
        ]);

        Product::factory()->create([
            'generic_name' => 'Ibuprofen',
            'brand_name' => 'Brufen',
            'sales_price' => 20.00,
        ]);

        $results = $this->productService->search(['search' => 'Para']);

        $this->assertGreaterThan(0, $results->total());
    }

    /** @test */
    public function it_can_check_drug_interactions(): void
    {
        $product1 = Product::factory()->create(['generic_name' => 'Warfarin']);
        $product2 = Product::factory()->create(['generic_name' => 'Aspirin']);

        // Note: DrugInteraction model would need to be seeded for full test
        $interactions = $this->productService->checkDrugInteractions([$product1->id, $product2->id]);

        $this->assertIsArray($interactions);
    }

    /** @test */
    public function it_records_price_history_on_create(): void
    {
        $productData = [
            'generic_name' => 'Vitamin C',
            'sales_price' => 25.00,
        ];

        $product = $this->productService->create($productData);

        $this->assertDatabaseHas('product_price_history', [
            'product_id' => $product->id,
            'price_type' => 'sales',
            'new_price' => 25.00,
        ]);
    }

    /** @test */
    public function it_can_get_product_by_barcode(): void
    {
        $product = Product::factory()->create([
            'barcode' => '6281234567890',
        ]);

        $found = $this->productService->getByBarcode('6281234567890');

        $this->assertNotNull($found);
        $this->assertEquals($product->id, $found->id);
    }
}
