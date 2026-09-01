<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class BaseFormRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_monetary_rules_validates_positive_amounts(): void
    {
        $request = new class extends BaseFormRequest {
            public function rules(): array
            {
                return [
                    'amount' => array_merge($this->monetaryRules(), ['required']),
                ];
            }
            public function authorize(): bool { return true; }
        };

        $validData = ['amount' => 19.99];
        $validator = Validator::make($validData, $request->rules());
        $this->assertTrue($validator->passes());

        $invalidData = ['amount' => -5];
        $validator = Validator::make($invalidData, $request->rules());
        $this->assertFalse($validator->passes());
    }

    public function test_positive_integer_rules_validates_correctly(): void
    {
        $request = new class extends BaseFormRequest {
            public function rules(): array
            {
                return [
                    'quantity' => array_merge($this->positiveIntegerRules(), ['required']),
                ];
            }
            public function authorize(): bool { return true; }
        };

        $validData = ['quantity' => 10];
        $validator = Validator::make($validData, $request->rules());
        $this->assertTrue($validator->passes());

        $invalidData = ['quantity' => -1];
        $validator = Validator::make($invalidData, $request->rules());
        $this->assertFalse($validator->passes());
    }

    public function test_phone_rules_validates_phone_format(): void
    {
        $request = new class extends BaseFormRequest {
            public function rules(): array
            {
                return [
                    'phone' => $this->phoneRules(),
                ];
            }
            public function authorize(): bool { return true; }
        };

        $validData = ['phone' => '0501234567'];
        $validator = Validator::make($validData, $request->rules());
        $this->assertTrue($validator->passes());
    }

    public function test_email_rules_validates_email_format(): void
    {
        $request = new class extends BaseFormRequest {
            public function rules(): array
            {
                return [
                    'email' => $this->emailRules(),
                ];
            }
            public function authorize(): bool { return true; }
        };

        $validData = ['email' => 'test@example.com'];
        $validator = Validator::make($validData, $request->rules());
        $this->assertTrue($validator->passes());

        $invalidData = ['email' => 'not-an-email'];
        $validator = Validator::make($invalidData, $request->rules());
        $this->assertFalse($validator->passes());
    }

    public function test_product_code_rules_validates_format(): void
    {
        $request = new class extends BaseFormRequest {
            public function rules(): array
            {
                return [
                    'code' => $this->productCodeRules(),
                ];
            }
            public function authorize(): bool { return true; }
        };

        $validData = ['code' => 'PRD-001'];
        $validator = Validator::make($validData, $request->rules());
        $this->assertTrue($validator->passes());
    }

    public function test_percentage_rules_validates_range(): void
    {
        $request = new class extends BaseFormRequest {
            public function rules(): array
            {
                return [
                    'discount' => $this->percentageRules(),
                ];
            }
            public function authorize(): bool { return true; }
        };

        $validData = ['discount' => 15];
        $validator = Validator::make($validData, $request->rules());
        $this->assertTrue($validator->passes());

        $invalidData = ['discount' => 150];
        $validator = Validator::make($invalidData, $request->rules());
        $this->assertFalse($validator->passes());
    }
}
