<?php

namespace Tests\Unit\Requests;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreProductRequestTest extends TestCase
{
    use RefreshDatabase;

    private array $rules = [
        'productName' => 'required|string|max:255',
        'productCode' => 'required|string|max:255|unique:products,productCode',
        'business_id' => 'required|exists:businesses,id',
    ];

    public function test_valid_data_passes_validation(): void
    {
        $business = Business::factory()->create();

        $validator = Validator::make([
            'productName' => 'Aspirin',
            'productCode' => 'ASP001',
            'business_id' => $business->id,
        ], $this->rules);

        $this->assertTrue($validator->passes());
    }

    public function test_missing_name_fails_validation(): void
    {
        $validator = Validator::make([
            'productCode' => 'ASP001',
            'business_id' => 1,
        ], $this->rules);

        $this->assertFalse($validator->passes());
    }

    public function test_missing_code_fails_validation(): void
    {
        $validator = Validator::make([
            'productName' => 'Aspirin',
            'business_id' => 1,
        ], $this->rules);

        $this->assertFalse($validator->passes());
    }
}
