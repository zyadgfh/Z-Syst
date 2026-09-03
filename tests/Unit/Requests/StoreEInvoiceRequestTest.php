<?php

namespace Tests\Unit\Requests;

use App\Models\Business;
use App\Models\Party;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreEInvoiceRequestTest extends TestCase
{
    use RefreshDatabase;

    private array $rules = [
        'party_id' => 'required|exists:parties,id',
        'invoice_date' => 'required|date',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.quantity' => 'required|integer|min:1',
    ];

    public function test_valid_data_passes_validation(): void
    {
        $business = Business::factory()->create();
        $party = Party::factory()->create(['business_id' => $business->id, 'type' => 'customer']);
        $product = Product::factory()->create(['business_id' => $business->id]);

        $validator = Validator::make([
            'party_id' => $party->id,
            'invoice_date' => '2026-08-20',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ], $this->rules);

        $this->assertTrue($validator->passes());
    }

    public function test_missing_party_id_fails_validation(): void
    {
        $validator = Validator::make([
            'invoice_date' => '2026-08-20',
            'items' => [['product_id' => 1, 'quantity' => 1]],
        ], $this->rules);

        $this->assertFalse($validator->passes());
    }

    public function test_empty_items_fails_validation(): void
    {
        $validator = Validator::make([
            'party_id' => 1,
            'invoice_date' => '2026-08-20',
            'items' => [],
        ], $this->rules);

        $this->assertFalse($validator->passes());
    }

    public function test_negative_quantity_fails_validation(): void
    {
        $validator = Validator::make([
            'party_id' => 1,
            'invoice_date' => '2026-08-20',
            'items' => [['product_id' => 1, 'quantity' => -1]],
        ], $this->rules);

        $this->assertFalse($validator->passes());
    }
}
