<?php

namespace Tests\Unit\Requests;

use App\Models\Business;
use App\Models\Party;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StorePurchaseRequestTest extends TestCase
{
    use RefreshDatabase;

    private array $rules = [
        'supplier_id' => 'required|exists:parties,id',
        'purchaseDate' => 'required|date',
        'items' => 'required|array|min:1',
    ];

    public function test_valid_data_passes_validation(): void
    {
        $business = Business::factory()->create();
        $supplier = Party::factory()->create(['business_id' => $business->id, 'type' => 'supplier']);
        $product = Product::factory()->create(['business_id' => $business->id]);

        $validator = Validator::make([
            'supplier_id' => $supplier->id,
            'purchaseDate' => '2026-08-20',
            'items' => [['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 10]],
        ], $this->rules);

        $this->assertTrue($validator->passes());
    }

    public function test_missing_supplier_id_fails_validation(): void
    {
        $validator = Validator::make([
            'purchaseDate' => '2026-08-20',
            'items' => [['product_id' => 1, 'quantity' => 5, 'unit_price' => 10]],
        ], $this->rules);

        $this->assertFalse($validator->passes());
    }

    public function test_missing_purchase_date_fails_validation(): void
    {
        $validator = Validator::make([
            'supplier_id' => 1,
            'items' => [['product_id' => 1, 'quantity' => 5, 'unit_price' => 10]],
        ], $this->rules);

        $this->assertFalse($validator->passes());
    }

    public function test_empty_items_fails_validation(): void
    {
        $validator = Validator::make([
            'supplier_id' => 1,
            'purchaseDate' => '2026-08-20',
            'items' => [],
        ], $this->rules);

        $this->assertFalse($validator->passes());
    }
}
