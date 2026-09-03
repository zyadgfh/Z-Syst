<?php

namespace Tests\Unit\Requests;

use App\Models\InsuranceCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreInsurancePolicyRequestTest extends TestCase
{
    use RefreshDatabase;

    private array $rules = [
        'insurance_company_id' => 'required|exists:insurance_companies,id',
    ];

    public function test_valid_data_passes_validation(): void
    {
        $company = InsuranceCompany::factory()->create();

        $validator = Validator::make([
            'insurance_company_id' => $company->id,
        ], $this->rules);

        $this->assertTrue($validator->passes());
    }

    public function test_missing_insurance_company_id_fails_validation(): void
    {
        $validator = Validator::make([], $this->rules);

        $this->assertFalse($validator->passes());
    }
}
