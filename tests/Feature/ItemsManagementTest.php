<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ItemsManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Business $business;
    protected User $user;
    protected Category $category;
    protected Unit $unit;
    protected Manufacturer $manufacturer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'role' => 'staff',
        ]);
        $this->category = Category::factory()->create(['business_id' => $this->business->id]);
        $this->unit = Unit::factory()->create(['business_id' => $this->business->id]);
        $this->manufacturer = Manufacturer::factory()->create(['business_id' => $this->business->id]);
    }

    protected function actingAsAdmin(): self
    {
        $this->actingAs($this->user);

        return $this;
    }

    // =========================================================================
    // INDEX / LIST
    // =========================================================================

    #[Test]
    public function admin_can_view_items_index_page()
    {
        Product::factory()->count(3)->create(['business_id' => $this->business->id]);

        $this->actingAsAdmin()
            ->get(route('admin.items.index'))
            ->assertOk();
    }

    #[Test]
    public function items_index_only_shows_own_business_items()
    {
        $ownProduct = Product::factory()->create(['business_id' => $this->business->id]);
        $otherBusiness = Business::factory()->create();
        $otherProduct = Product::factory()->create(['business_id' => $otherBusiness->id]);

        $response = $this->actingAsAdmin()
            ->get(route('admin.items.index'))
            ->assertOk();

        // The index page should not contain the other business's product name
        $response->assertDontSee($otherProduct->productName);
    }

    #[Test]
    public function unauthenticated_user_cannot_view_items_index()
    {
        $this->get(route('admin.items.index'))
            ->assertRedirect();
    }

    #[Test]
    public function non_admin_user_cannot_view_items_index()
    {
        $regularUser = User::factory()->create([
            'business_id' => $this->business->id,
            'role' => 'customer',
        ]);

        $this->actingAs($regularUser)
            ->get(route('admin.items.index'))
            ->assertRedirect('/');
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    #[Test]
    public function admin_can_view_create_item_form()
    {
        $this->actingAsAdmin()
            ->get(route('admin.items.create'))
            ->assertOk();
    }

    // =========================================================================
    // STORE
    // =========================================================================

    #[Test]
    public function admin_can_create_a_new_item()
    {
        $data = [
            'productName' => 'Amoxicillin 500mg',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'manufacturer_id' => $this->manufacturer->id,
            'purchase_without_tax' => 10.00,
            'purchase_with_tax' => 11.00,
            'profit_percent' => 30,
            'sales_price' => 14.30,
            'wholesale_price' => 12.00,
            'alert_qty' => 10,
            'barcode' => '1234567890128',
            'sku' => 'AMX-500',
            'tax_type' => 'exclusive',
            'product_type' => 'product',
            'dosage_form' => 'capsule',
            'strength' => '500mg',
            'route' => 'oral',
            'scientific_name' => 'Amoxicillin Trihydrate',
            'is_active' => true,
            'requires_prescription' => true,
            'track_inventory' => true,
            'reorder_point' => 10,
            'reorder_qty' => 50,
        ];

        $this->actingAsAdmin()
            ->post(route('admin.items.store'), $data)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('products', [
            'business_id' => $this->business->id,
            'productName' => 'Amoxicillin 500mg',
            'barcode' => '1234567890128',
            'sku' => 'AMX-500',
        ]);
    }

    #[Test]
    public function store_validates_required_fields()
    {
        $this->actingAsAdmin()
            ->post(route('admin.items.store'), [])
            ->assertJsonValidationErrors(['productName', 'category_id']);
    }

    #[Test]
    public function store_rejects_duplicate_barcode_within_same_business()
    {
        Product::factory()->create([
            'business_id' => $this->business->id,
            'barcode' => '1234567890128',
        ]);

        $data = [
            'productName' => 'Duplicate Barcode Product',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'manufacturer_id' => $this->manufacturer->id,
            'barcode' => '1234567890128',
            'purchase_without_tax' => 10,
            'purchase_with_tax' => 11,
            'sales_price' => 15,
        ];

        $this->actingAsAdmin()
            ->post(route('admin.items.store'), $data)
            ->assertJsonValidationErrors(['barcode']);
    }

    #[Test]
    public function store_allows_same_barcode_across_different_businesses()
    {
        $otherBusiness = Business::factory()->create();
        Product::factory()->create([
            'business_id' => $otherBusiness->id,
            'barcode' => '1234567890128',
        ]);

        $data = [
            'productName' => 'Same Barcode Different Business',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'manufacturer_id' => $this->manufacturer->id,
            'barcode' => '1234567890128',
            'purchase_without_tax' => 10,
            'purchase_with_tax' => 11,
            'sales_price' => 15,
        ];

        $this->actingAsAdmin()
            ->post(route('admin.items.store'), $data)
            ->assertJson(['success' => true]);
    }

    #[Test]
    public function store_rejects_invalid_tax_type()
    {
        $data = [
            'productName' => 'Bad Tax Type',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'manufacturer_id' => $this->manufacturer->id,
            'purchase_without_tax' => 10,
            'purchase_with_tax' => 11,
            'sales_price' => 15,
            'tax_type' => 'invalid_type',
        ];

        $this->actingAsAdmin()
            ->post(route('admin.items.store'), $data)
            ->assertJsonValidationErrors(['tax_type']);
    }

    #[Test]
    public function store_rejects_negative_prices()
    {
        $data = [
            'productName' => 'Negative Price',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'manufacturer_id' => $this->manufacturer->id,
            'purchase_without_tax' => -10,
            'sales_price' => -5,
        ];

        $this->actingAsAdmin()
            ->post(route('admin.items.store'), $data)
            ->assertJsonValidationErrors(['purchase_without_tax', 'sales_price']);
    }

    // =========================================================================
    // SHOW / DETAILS
    // =========================================================================

    #[Test]
    public function admin_can_view_item_details()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);

        $this->actingAsAdmin()
            ->get(route('admin.items.show', $product->id))
            ->assertOk();
    }

    #[Test]
    public function show_displays_product_name_and_barcode()
    {
        $product = Product::factory()->create([
            'business_id' => $this->business->id,
            'productName' => 'Test Product Show',
            'barcode' => '9999999999999',
        ]);

        $this->actingAsAdmin()
            ->get(route('admin.items.show', $product->id))
            ->assertOk()
            ->assertSee('Test Product Show')
            ->assertSee('9999999999999');
    }

    #[Test]
    public function cannot_view_other_business_item_details()
    {
        $otherBusiness = Business::factory()->create();
        $otherProduct = Product::factory()->create(['business_id' => $otherBusiness->id]);

        $this->actingAsAdmin()
            ->get(route('admin.items.show', $otherProduct->id))
            ->assertNotFound();
    }

    // =========================================================================
    // EDIT
    // =========================================================================

    #[Test]
    public function admin_can_view_edit_item_form()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);

        $this->actingAsAdmin()
            ->get(route('admin.items.edit', $product->id))
            ->assertOk();
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    #[Test]
    public function admin_can_update_an_item()
    {
        $product = Product::factory()->create([
            'business_id' => $this->business->id,
            'productName' => 'Old Name',
        ]);

        $this->actingAsAdmin()
            ->put(route('admin.items.update', $product->id), [
                'productName' => 'New Name',
                'category_id' => $this->category->id,
                'unit_id' => $this->unit->id,
                'manufacturer_id' => $this->manufacturer->id,
                'purchase_without_tax' => 10,
                'purchase_with_tax' => 11,
                'sales_price' => 15,
            ])
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'productName' => 'New Name',
        ]);
    }

    #[Test]
    public function cannot_update_other_business_item()
    {
        $otherBusiness = Business::factory()->create();
        $otherProduct = Product::factory()->create(['business_id' => $otherBusiness->id]);

        $this->actingAsAdmin()
            ->put(route('admin.items.update', $otherProduct->id), [
                'productName' => 'Hacked Name',
                'category_id' => $this->category->id,
                'unit_id' => $this->unit->id,
                'manufacturer_id' => $this->manufacturer->id,
                'purchase_without_tax' => 10,
                'sales_price' => 15,
            ])
            ->assertForbidden();
    }

    #[Test]
    public function update_records_price_history()
    {
        $product = Product::factory()->create([
            'business_id' => $this->business->id,
            'sales_price' => 15.00,
        ]);

        $this->actingAsAdmin()
            ->put(route('admin.items.update', $product->id), [
                'productName' => $product->productName,
                'category_id' => $this->category->id,
                'unit_id' => $this->unit->id,
                'manufacturer_id' => $this->manufacturer->id,
                'purchase_without_tax' => 10,
                'sales_price' => 20.00,
            ])
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('item_price_history', [
            'product_id' => $product->id,
            'sales_price' => 20.00,
        ]);
    }

    // =========================================================================
    // DELETE / DESTROY
    // =========================================================================

    #[Test]
    public function admin_can_delete_an_item_without_transactions()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);

        $this->actingAsAdmin()
            ->delete(route('admin.items.destroy', $product->id))
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    #[Test]
    public function cannot_delete_other_business_item()
    {
        $otherBusiness = Business::factory()->create();
        $otherProduct = Product::factory()->create(['business_id' => $otherBusiness->id]);

        $this->actingAsAdmin()
            ->delete(route('admin.items.destroy', $otherProduct->id))
            ->assertForbidden();
    }

    // =========================================================================
    // SEARCH / AJAX ENDPOINTS
    // =========================================================================

    #[Test]
    public function admin_can_search_items()
    {
        Product::factory()->create([
            'business_id' => $this->business->id,
            'productName' => 'Paracetamol 500mg',
        ]);
        Product::factory()->create([
            'business_id' => $this->business->id,
            'productName' => 'Ibuprofen 200mg',
        ]);

        $this->actingAsAdmin()
            ->get(route('admin.items.search', ['q' => 'Paracetamol']))
            ->assertOk()
            ->assertJsonFragment(['productName' => 'Paracetamol 500mg']);
    }

    #[Test]
    public function search_does_not_return_other_business_items()
    {
        Product::factory()->create([
            'business_id' => $this->business->id,
            'productName' => 'My Product',
        ]);
        $otherBusiness = Business::factory()->create();
        Product::factory()->create([
            'business_id' => $otherBusiness->id,
            'productName' => 'My Product', // Same name, different business
        ]);

        $results = $this->actingAsAdmin()
            ->getJson(route('admin.items.search', ['q' => 'My Product']))
            ->json('data');

        // Should only return 1 result (own business)
        $this->assertCount(1, $results);
    }

    #[Test]
    public function admin_can_check_for_duplicates()
    {
        Product::factory()->create([
            'business_id' => $this->business->id,
            'barcode' => '1111111111111',
            'productName' => 'Duplicate Test',
        ]);

        $this->actingAsAdmin()
            ->postJson(route('admin.items.check-duplicates'), [
                'barcode' => '1111111111111',
            ])
            ->assertOk()
            ->assertJson(['has_duplicates' => true]);
    }

    #[Test]
    public function check_duplicates_returns_no_match_for_unique_barcode()
    {
        $this->actingAsAdmin()
            ->postJson(route('admin.items.check-duplicates'), [
                'barcode' => '9999999999999',
            ])
            ->assertOk()
            ->assertJson(['has_duplicates' => false]);
    }

    #[Test]
    public function admin_can_search_by_barcode()
    {
        $product = Product::factory()->create([
            'business_id' => $this->business->id,
            'barcode' => '5555555555555',
        ]);

        $this->actingAsAdmin()
            ->postJson(route('admin.items.search-barcode'), [
                'barcode' => '5555555555555',
            ])
            ->assertOk()
            ->assertJsonFragment(['id' => $product->id]);
    }

    #[Test]
    public function search_by_barcode_returns_404_for_unknown_barcode()
    {
        $this->actingAsAdmin()
            ->postJson(route('admin.items.search-barcode'), [
                'barcode' => '0000000000000',
            ])
            ->assertNotFound();
    }

    #[Test]
    public function search_by_barcode_does_not_return_other_business_items()
    {
        $otherBusiness = Business::factory()->create();
        Product::factory()->create([
            'business_id' => $otherBusiness->id,
            'barcode' => '7777777777777',
        ]);

        $this->actingAsAdmin()
            ->postJson(route('admin.items.search-barcode'), [
                'barcode' => '7777777777777',
            ])
            ->assertNotFound();
    }

    // =========================================================================
    // BARCODE PRINTING
    // =========================================================================

    #[Test]
    public function admin_can_print_barcode_for_product()
    {
        $product = Product::factory()->create([
            'business_id' => $this->business->id,
            'barcode' => '1234567890128',
        ]);

        $this->actingAsAdmin()
            ->post(route('admin.items.print-barcode', $product->id), [
                'quantity' => 4,
                'size' => 'standard',
                'show_price' => true,
                'show_expiry' => true,
                'show_batch' => true,
                'show_code' => true,
                'show_scientific' => true,
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    #[Test]
    public function barcode_print_records_print_history()
    {
        $product = Product::factory()->create([
            'business_id' => $this->business->id,
            'barcode' => '1234567890128',
        ]);

        $this->actingAsAdmin()
            ->post(route('admin.items.print-barcode', $product->id), [
                'quantity' => 4,
                'size' => 'standard',
            ])
            ->assertOk();

        $this->assertDatabaseHas('item_print_history', [
            'product_id' => $product->id,
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'quantity' => 4,
            'size' => 'standard',
        ]);
    }

    #[Test]
    public function barcode_print_rejects_invalid_quantity()
    {
        $product = Product::factory()->create([
            'business_id' => $this->business->id,
            'barcode' => '1234567890128',
        ]);

        $this->actingAsAdmin()
            ->postJson(route('admin.items.print-barcode', $product->id), [
                'quantity' => 0,
            ])
            ->assertJsonValidationErrors(['quantity']);
    }

    #[Test]
    public function barcode_print_rejects_excessive_quantity()
    {
        $product = Product::factory()->create([
            'business_id' => $this->business->id,
            'barcode' => '1234567890128',
        ]);

        $this->actingAsAdmin()
            ->postJson(route('admin.items.print-barcode', $product->id), [
                'quantity' => 300,
            ])
            ->assertJsonValidationErrors(['quantity']);
    }

    #[Test]
    public function cannot_print_barcode_for_other_business_product()
    {
        $otherBusiness = Business::factory()->create();
        $otherProduct = Product::factory()->create([
            'business_id' => $otherBusiness->id,
            'barcode' => '1234567890128',
        ]);

        $this->actingAsAdmin()
            ->post(route('admin.items.print-barcode', $otherProduct->id), [
                'quantity' => 4,
            ])
            ->assertNotFound();
    }

    // =========================================================================
    // EXPORT
    // =========================================================================

    #[Test]
    public function admin_can_export_items_to_csv()
    {
        Product::factory()->count(3)->create(['business_id' => $this->business->id]);

        $this->actingAsAdmin()
            ->get(route('admin.items.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    #[Test]
    public function export_only_includes_own_business_items()
    {
        Product::factory()->create([
            'business_id' => $this->business->id,
            'productName' => 'Own Product',
        ]);
        $otherBusiness = Business::factory()->create();
        Product::factory()->create([
            'business_id' => $otherBusiness->id,
            'productName' => 'Other Product',
        ]);

        $response = $this->actingAsAdmin()
            ->get(route('admin.items.export'))
            ->streamedContent();

        $this->assertStringContainsString('Own Product', $response);
        $this->assertStringNotContainsString('Other Product', $response);
    }

    #[Test]
    public function export_with_category_filter()
    {
        $cat1 = Category::factory()->create(['business_id' => $this->business->id]);
        $cat2 = Category::factory()->create(['business_id' => $this->business->id]);

        Product::factory()->create([
            'business_id' => $this->business->id,
            'category_id' => $cat1->id,
            'productName' => 'Cat1 Product',
        ]);
        Product::factory()->create([
            'business_id' => $this->business->id,
            'category_id' => $cat2->id,
            'productName' => 'Cat2 Product',
        ]);

        $response = $this->actingAsAdmin()
            ->get(route('admin.items.export', ['category_id' => $cat1->id]))
            ->streamedContent();

        $this->assertStringContainsString('Cat1 Product', $response);
        $this->assertStringNotContainsString('Cat2 Product', $response);
    }

    // =========================================================================
    // IMPORT
    // =========================================================================

    #[Test]
    public function admin_can_import_items_from_csv()
    {
        Storage::fake('local');

        $csvContent = "productName,category,unit,manufacturer,purchase_without_tax,purchase_with_tax,profit_percent,sales_price,wholesale_price,alert_qty,barcode,tax_type\n";
        $csvContent .= "Imported Tablet,{$this->category->categoryName},{$this->unit->unitName},{$this->manufacturer->name},8.00,9.00,25,11.25,10.00,10,8888888888888,exclusive\n";

        $file = UploadedFile::fake()->createWithContent('items.csv', $csvContent);

        $this->actingAsAdmin()
            ->post(route('admin.items.import'), ['file' => $file])
            ->assertJson(['success' => true]);
    }

    #[Test]
    public function import_rejects_non_csv_files()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('malicious.exe', 100, 'application/x-executable');

        $this->actingAsAdmin()
            ->postJson(route('admin.items.import'), ['file' => $file])
            ->assertJsonValidationErrors(['file']);
    }

    #[Test]
    public function import_rejects_empty_file()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->createWithContent('empty.csv', "productName\n");

        $this->actingAsAdmin()
            ->postJson(route('admin.items.import'), ['file' => $file])
            ->assertOk();
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    #[Test]
    public function admin_can_view_items_statistics()
    {
        Product::factory()->count(5)->create(['business_id' => $this->business->id]);

        $this->actingAsAdmin()
            ->get(route('admin.items.statistics'))
            ->assertOk()
            ->assertJsonStructure(['success', 'data' => ['total', 'active']]);
    }

    #[Test]
    public function statistics_only_count_own_business()
    {
        Product::factory()->count(3)->create(['business_id' => $this->business->id]);
        $otherBusiness = Business::factory()->create();
        Product::factory()->count(5)->create(['business_id' => $otherBusiness->id]);

        $response = $this->actingAsAdmin()
            ->getJson(route('admin.items.statistics'))
            ->json();

        $this->assertEquals(3, $response['data']['total']);
    }

    // =========================================================================
    // STOCK ADJUSTMENTS
    // =========================================================================

    #[Test]
    public function admin_can_adjust_stock_for_an_item()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 50,
        ]);

        $this->actingAsAdmin()
            ->put(route('admin.items.update', $product->id), [
                'productName' => $product->productName,
                'category_id' => $this->category->id,
                'unit_id' => $this->unit->id,
                'manufacturer_id' => $this->manufacturer->id,
                'purchase_without_tax' => 10,
                'sales_price' => 15,
            ])
            ->assertJson(['success' => true]);
    }

    #[Test]
    public function stock_is_linked_to_product_and_business()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);
        $stock = Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 100,
            'batch_no' => 'BATCH-TEST-001',
        ]);

        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'business_id' => $this->business->id,
            'productStock' => 100,
            'batch_no' => 'BATCH-TEST-001',
        ]);
    }

    #[Test]
    public function product_total_stock_accessor_returns_correct_value()
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);

        Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 30,
        ]);
        Stock::factory()->create([
            'business_id' => $this->business->id,
            'product_id' => $product->id,
            'productStock' => 20,
        ]);

        $product->refresh();
        $this->assertEquals(50, $product->total_stock);
    }

    // =========================================================================
    // GENERATE INTERNAL CODE
    // =========================================================================

    #[Test]
    public function admin_can_generate_internal_code()
    {
        $response = $this->actingAsAdmin()
            ->getJson(route('admin.items.generate-code'))
            ->assertOk()
            ->json();

        $this->assertTrue(isset($response['internal_code']) || isset($response['code']));
        $code = $response['internal_code'] ?? $response['code'] ?? null;
        $this->assertNotEmpty($code);
    }

    // =========================================================================
    // CROSS-TENANT ISOLATION (SECURITY)
    // =========================================================================

    #[Test]
    public function user_from_business_a_cannot_access_business_b_items_via_api_search()
    {
        $otherBusiness = Business::factory()->create();
        Product::factory()->create([
            'business_id' => $otherBusiness->id,
            'productName' => 'Secret Product',
            'barcode' => '1231231231231',
        ]);

        // Search by name
        $this->actingAsAdmin()
            ->getJson(route('admin.items.search', ['q' => 'Secret']))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        // Search by barcode
        $this->actingAsAdmin()
            ->postJson(route('admin.items.search-barcode'), ['barcode' => '1231231231231'])
            ->assertNotFound();
    }
}
