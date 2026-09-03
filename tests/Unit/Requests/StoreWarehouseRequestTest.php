<?php

namespace Tests\Unit\Requests;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreWarehouseRequestTest extends TestCase
{
    use RefreshDatabase;

    private array $rules = [
        'name' => 'required|string|max:255',
        'business_id' => 'required|exists:businesses,id',
    ];

    public function test_valid_data_passes_validation(): void
    {
        $business = Business::factory()->create();

        $validator = Validator::make([
            'name' => 'Main Warehouse',
            'business_id' => $business->id,
        ], $this->rules);

        $this->assertTrue($validator->passes());
    }

    public function test_missing_name_fails_validation(): void
    {
        $validator = Validator::make([
            'business_id' => 1,
        ], $this->rules);

        $this->assertFalse($validator->passes());
    }

    public function test_missing_business_id_fails_validation(): void
    {
        $validator = Validator::make([
            'name' => 'Warehouse',
        ], $this->rules);

        $this->assertFalse($validator->passes());
    }
}
