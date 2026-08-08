<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\ReceiptSetting;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptApiTest extends TestCase
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

    public function test_can_get_receipt_settings(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/receipts/settings');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['receipt_header', 'receipt_footer', 'show_barcode', 'show_qr_code', 'is_active'],
            ]);
    }

    public function test_can_update_receipt_settings(): void
    {
        $settings = ReceiptSetting::create([
            'business_id' => $this->business->id,
            'receipt_header' => 'Original Header',
            'receipt_footer' => 'Original Footer',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/receipts/settings', [
                'receipt_header' => 'Updated Header',
                'receipt_footer' => 'Updated Footer',
                'show_barcode' => false,
                'show_qr_code' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.receipt_header', 'Updated Header')
            ->assertJsonPath('data.show_qr_code', true);
    }

    public function test_can_generate_receipt_for_sale(): void
    {
        $product = Product::factory()->create(['business_id' => $this->business->id]);

        $sale = Sale::create([
            'business_id' => $this->business->id,
            'party_id' => null,
            'user_id' => $this->user->id,
            'totalAmount' => 150.0,
            'paidAmount' => 150.0,
            'dueAmount' => 0.0,
            'isPaid' => true,
            'paymentType' => 'cash',
            'saleDate' => now()->toDateString(),
        ]);

        SaleDetails::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'price' => 150.0,
            'quantities' => 1,
            'batch_no' => 'BATCH-001',
            'expire_date' => now()->addYear()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/receipts/sales/{$sale->id}");

        $response->assertCreated()
            ->assertJsonPath('data.type', 'sale')
            ->assertJsonPath('data.status', 'generated');

        $this->assertDatabaseHas('receipts', [
            'business_id' => $this->business->id,
            'sale_id' => $sale->id,
            'type' => 'sale',
        ]);
    }

    public function test_can_show_receipt(): void
    {
        $receipt = Receipt::create([
            'business_id' => $this->business->id,
            'receipt_number' => 'RCPT-001',
            'type' => 'sale',
            'format' => 'pdf',
            'status' => 'generated',
            'user_id' => $this->user->id,
            'data' => [],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/receipts/{$receipt->id}");

        $response->assertOk()
            ->assertJsonPath('data.receipt_number', 'RCPT-001');
    }

    public function test_cannot_generate_receipt_for_other_business_sale(): void
    {
        $otherBusiness = Business::factory()->create();
        $sale = Sale::create([
            'business_id' => $otherBusiness->id,
            'user_id' => $this->user->id,
            'totalAmount' => 100.0,
            'paidAmount' => 100.0,
            'dueAmount' => 0.0,
            'isPaid' => true,
            'paymentType' => 'cash',
            'saleDate' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/receipts/sales/{$sale->id}");

        $response->assertForbidden();
    }
}
