<?php

namespace Tests\Unit\Requests;

use App\Models\Business;
use App\Models\Party;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreSaleRequestTest extends TestCase
{
    use RefreshDatabase;

    private array $rules = [
        'customer_id' => 'required|exists:parties,id',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.quantity' => 'required|integer|min:1',
    ];

    public function test_valid_data_passes_validation(): void
    {
        $business = Business::factory()->create();
        $customer = Party::factory()->create(['business_id' => $business->id, 'type' => 'customer']);
        $product = Product::factory()->create(['business_id' => $business->id]);

        $validator = Validator::make([
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ], $this->rules);

        $this->assertTrue($validator->passes());
    }

    public function test_missing_customer_id_fails_validation(): void
    {
        $validator = Validator::make([
            'items' => [['product_id' => 1, 'quantity' => 2]],
        ], $this->rules);

        $this->assertFalse($validator->passes());
    }

    public function test_empty_items_fails_validation(): void
    {
        $validator = Validator::make([
            'customer_id' => 1,
            'items' => [],
        ], $this->rules);

        $this->assertFalse($validator->passes());
    }
}
