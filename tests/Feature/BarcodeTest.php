<?php

namespace Tests\Feature;

use App\Models\Barcode;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Services\BarcodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BarcodeTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected BarcodeService $barcodeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->barcodeService = new BarcodeService();
    }

    /**
     * Test barcode generation for product.
     */
    public function test_can_generate_barcode_for_product()
    {
        $product = Product::factory()->create();
        $barcode = $this->barcodeService->generateForProduct($product);

        $this->assertDatabaseHas('barcodes', [
            'id' => $barcode->id,
            'product_id' => $product->id,
            'barcode_number' => $barcode->barcode_number,
        ]);

        $this->assertEquals(Barcode::STATUS_NOT_PRINTED, $barcode->print_status);
        $this->assertEquals(0, $barcode->print_count);
    }

    /**
     * Test barcode generation for batch.
     */
    public function test_can_generate_barcode_for_batch()
    {
        $stock = Stock::factory()->create();
        $barcode = $this->barcodeService->generateForBatch($stock);

        $this->assertDatabaseHas('barcodes', [
            'id' => $barcode->id,
            'product_id' => $stock->product_id,
            'batch_id' => $stock->id,
        ]);

        $this->assertEquals(Barcode::STATUS_NOT_PRINTED, $barcode->print_status);
    }

    /**
     * Test generating multiple barcodes for product.
     */
    public function test_can_generate_multiple_barcodes_for_product()
    {
        $product = Product::factory()->create();
        $quantity = 5;
        $barcodes = $this->barcodeService->generateMultipleForProduct($product, $quantity);

        $this->assertCount($quantity, $barcodes);
        $this->assertEquals($quantity, Barcode::where('product_id', $product->id)->count());
    }

    /**
     * Test generating multiple barcodes for batch.
     */
    public function test_can_generate_multiple_barcodes_for_batch()
    {
        $stock = Stock::factory()->create();
        $quantity = 3;
        $barcodes = $this->barcodeService->generateMultipleForBatch($stock, $quantity);

        $this->assertCount($quantity, $barcodes);
        $this->assertEquals($quantity, Barcode::where('batch_id', $stock->id)->count());
    }

    /**
     * Test barcode number generation for different types.
     */
    public function test_barcode_number_generation()
    {
        $code128 = Barcode::generateBarcodeNumber(Barcode::TYPE_CODE128);
        $ean13 = Barcode::generateBarcodeNumber(Barcode::TYPE_EAN13);
        $upc = Barcode::generateBarcodeNumber(Barcode::TYPE_UPC);
        $qr = Barcode::generateBarcodeNumber(Barcode::TYPE_QR);

        $this->assertStringStartsWith('BC', $code128);
        $this->assertEquals(13, strlen($ean13));
        $this->assertEquals(12, strlen($upc));
        $this->assertStringStartsWith('QR-', $qr);
    }

    /**
     * Test EAN13 checksum calculation.
     */
    public function test_ean13_checksum_calculation()
    {
        $code = '600123456789';
        $checksum = Barcode::calculateEAN13Checksum($code);
        
        $this->assertIsInt($checksum);
        $this->assertGreaterThanOrEqual(0, $checksum);
        $this->assertLessThanOrEqual(9, $checksum);
    }

    /**
     * Test UPC checksum calculation.
     */
    public function test_upc_checksum_calculation()
    {
        $code = '12345678901';
        $checksum = Barcode::calculateUPCChecksum($code);
        
        $this->assertIsInt($checksum);
        $this->assertGreaterThanOrEqual(0, $checksum);
        $this->assertLessThanOrEqual(9, $checksum);
    }

    /**
     * Test EAN13 validation.
     */
    public function test_ean13_validation()
    {
        $validEAN13 = '6001234567892';
        $invalidEAN13 = '6001234567899';

        $this->assertTrue($this->barcodeService->validateBarcodeNumber($validEAN13, Barcode::TYPE_EAN13));
        $this->assertFalse($this->barcodeService->validateBarcodeNumber($invalidEAN13, Barcode::TYPE_EAN13));
    }

    /**
     * Test UPC validation.
     */
    public function test_upc_validation()
    {
        $validUPC = '123456789012';
        $invalidUPC = '123456789019';

        $this->assertTrue($this->barcodeService->validateBarcodeNumber($validUPC, Barcode::TYPE_UPC));
        $this->assertFalse($this->barcodeService->validateBarcodeNumber($invalidUPC, Barcode::TYPE_UPC));
    }

    /**
     * Test marking barcode as printed.
     */
    public function test_can_mark_barcode_as_printed()
    {
        $barcode = Barcode::factory()->create([
            'print_status' => Barcode::STATUS_NOT_PRINTED,
            'print_count' => 0,
        ]);

        $userId = User::factory()->create()->id;
        $barcode->markAsPrinted($userId);

        $this->assertEquals(Barcode::STATUS_PRINTED, $barcode->print_status);
        $this->assertEquals(1, $barcode->print_count);
        $this->assertEquals($userId, $barcode->printed_by);
        $this->assertNotNull($barcode->printed_at);
    }

    /**
     * Test marking barcode as reprinted.
     */
    public function test_can_mark_barcode_as_reprinted()
    {
        $barcode = Barcode::factory()->create([
            'print_status' => Barcode::STATUS_PRINTED,
            'print_count' => 1,
        ]);

        $userId = User::factory()->create()->id;
        $barcode->markAsPrinted($userId);

        $this->assertEquals(Barcode::STATUS_REPRINTED, $barcode->print_status);
        $this->assertEquals(2, $barcode->print_count);
    }

    /**
     * Test barcode scope for business.
     */
    public function test_barcode_scope_for_business()
    {
        $businessId = 1;
        Barcode::factory()->create(['business_id' => $businessId]);
        Barcode::factory()->create(['business_id' => 2]);

        $barcodes = Barcode::forBusiness($businessId)->get();

        $this->assertCount(1, $barcodes);
        $this->assertEquals($businessId, $barcodes->first()->business_id);
    }

    /**
     * Test barcode scope for product.
     */
    public function test_barcode_scope_for_product()
    {
        $product = Product::factory()->create();
        Barcode::factory()->create(['product_id' => $product->id]);
        Barcode::factory()->create(['product_id' => Product::factory()->create()->id]);

        $barcodes = Barcode::forProduct($product->id)->get();

        $this->assertCount(1, $barcodes);
        $this->assertEquals($product->id, $barcodes->first()->product_id);
    }

    /**
     * Test barcode scope for batch.
     */
    public function test_barcode_scope_for_batch()
    {
        $stock = Stock::factory()->create();
        Barcode::factory()->create(['batch_id' => $stock->id]);
        Barcode::factory()->create(['batch_id' => Stock::factory()->create()->id]);

        $barcodes = Barcode::forBatch($stock->id)->get();

        $this->assertCount(1, $barcodes);
        $this->assertEquals($stock->id, $barcodes->first()->batch_id);
    }

    /**
     * Test barcode scope by print status.
     */
    public function test_barcode_scope_by_print_status()
    {
        Barcode::factory()->create(['print_status' => Barcode::STATUS_NOT_PRINTED]);
        Barcode::factory()->create(['print_status' => Barcode::STATUS_PRINTED]);

        $notPrinted = Barcode::byPrintStatus(Barcode::STATUS_NOT_PRINTED)->get();
        $printed = Barcode::byPrintStatus(Barcode::STATUS_PRINTED)->get();

        $this->assertCount(1, $notPrinted);
        $this->assertCount(1, $printed);
    }

    /**
     * Test barcode scope active.
     */
    public function test_barcode_scope_active()
    {
        Barcode::factory()->create(['is_active' => true]);
        Barcode::factory()->create(['is_active' => false]);

        $activeBarcodes = Barcode::active()->get();

        $this->assertCount(1, $activeBarcodes);
        $this->assertTrue($activeBarcodes->first()->is_active);
    }

    /**
     * Test barcode scope not printed.
     */
    public function test_barcode_scope_not_printed()
    {
        Barcode::factory()->create(['print_status' => Barcode::STATUS_NOT_PRINTED]);
        Barcode::factory()->create(['print_status' => Barcode::STATUS_PRINTED]);

        $notPrinted = Barcode::notPrinted()->get();

        $this->assertCount(1, $notPrinted);
        $this->assertEquals(Barcode::STATUS_NOT_PRINTED, $notPrinted->first()->print_status);
    }

    /**
     * Test product relationship with barcodes.
     */
    public function test_product_has_barcodes()
    {
        $product = Product::factory()->create();
        $barcode = Barcode::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(Barcode::class, $product->barcodes->first());
        $this->assertEquals($barcode->id, $product->barcodes->first()->id);
    }

    /**
     * Test stock relationship with barcodes.
     */
    public function test_stock_has_barcodes()
    {
        $stock = Stock::factory()->create();
        $barcode = Barcode::factory()->create(['batch_id' => $stock->id]);

        $this->assertInstanceOf(Barcode::class, $stock->barcodes->first());
        $this->assertEquals($barcode->id, $stock->barcodes->first()->id);
    }

    /**
     * Test barcode deletion.
     */
    public function test_can_delete_barcode()
    {
        $barcode = Barcode::factory()->create();
        $barcodeId = $barcode->id;

        $this->barcodeService->deleteBarcode($barcode);

        $this->assertSoftDeleted('barcodes', ['id' => $barcodeId]);
    }

    /**
     * Test barcode restoration.
     */
    public function test_can_restore_barcode()
    {
        $barcode = Barcode::factory()->create();
        $barcode->delete();
        $barcodeId = $barcode->id;

        $this->barcodeService->restoreBarcode($barcodeId);

        $this->assertDatabaseHas('barcodes', [
            'id' => $barcodeId,
            'deleted_at' => null,
        ]);
    }

    /**
     * Test barcode search by number.
     */
    public function test_can_search_barcode_by_number()
    {
        $businessId = 1;
        $barcode = Barcode::factory()->create([
            'barcode_number' => 'BC123456789',
            'business_id' => $businessId,
        ]);

        $foundBarcode = $this->barcodeService->searchByNumber('BC123456789', $businessId);

        $this->assertNotNull($foundBarcode);
        $this->assertEquals($barcode->id, $foundBarcode->id);
    }

    /**
     * Test getting barcodes by product.
     */
    public function test_can_get_barcodes_by_product()
    {
        $product = Product::factory()->create();
        $businessId = 1;
        
        Barcode::factory()->create([
            'product_id' => $product->id,
            'business_id' => $businessId,
        ]);
        Barcode::factory()->create([
            'product_id' => Product::factory()->create()->id,
            'business_id' => $businessId,
        ]);

        $barcodes = $this->barcodeService->getByProduct($product->id, $businessId);

        $this->assertCount(1, $barcodes);
        $this->assertEquals($product->id, $barcodes->first()->product_id);
    }

    /**
     * Test getting barcodes by batch.
     */
    public function test_can_get_barcodes_by_batch()
    {
        $stock = Stock::factory()->create();
        $businessId = 1;
        
        Barcode::factory()->create([
            'batch_id' => $stock->id,
            'business_id' => $businessId,
        ]);
        Barcode::factory()->create([
            'batch_id' => Stock::factory()->create()->id,
            'business_id' => $businessId,
        ]);

        $barcodes = $this->barcodeService->getByBatch($stock->id, $businessId);

        $this->assertCount(1, $barcodes);
        $this->assertEquals($stock->id, $barcodes->first()->batch_id);
    }

    /**
     * Test getting not printed barcodes.
     */
    public function test_can_get_not_printed_barcodes()
    {
        $businessId = 1;
        
        Barcode::factory()->create([
            'print_status' => Barcode::STATUS_NOT_PRINTED,
            'business_id' => $businessId,
        ]);
        Barcode::factory()->create([
            'print_status' => Barcode::STATUS_PRINTED,
            'business_id' => $businessId,
        ]);

        $notPrinted = $this->barcodeService->getNotPrinted($businessId);

        $this->assertCount(1, $notPrinted);
        $this->assertEquals(Barcode::STATUS_NOT_PRINTED, $notPrinted->first()->print_status);
    }

    /**
     * Test barcode API endpoint - create barcode.
     */
    public function test_api_can_create_barcode()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/admin/barcodes', [
                'product_id' => $product->id,
                'barcode_type' => Barcode::TYPE_CODE128,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'barcode_number',
                    'print_status',
                ],
            ]);
    }

    /**
     * Test barcode API endpoint - list barcodes.
     */
    public function test_api_can_list_barcodes()
    {
        $user = User::factory()->create();
        Barcode::factory()->count(3)->create(['business_id' => $user->business_id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/barcodes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'barcode_number',
                        'print_status',
                    ],
                ],
            ]);
    }

    /**
     * Test barcode API endpoint - search barcode.
     */
    public function test_api_can_search_barcode()
    {
        $user = User::factory()->create();
        $barcode = Barcode::factory()->create([
            'barcode_number' => 'BC123456789',
            'business_id' => $user->business_id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/barcodes/search?barcode_number=BC123456789');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $barcode->id,
                    'barcode_number' => 'BC123456789',
                ],
            ]);
    }
}
