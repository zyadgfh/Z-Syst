<?php

namespace Tests\Unit\Requests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreInsuranceCompanyRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_data_passes_validation(): void
    {
        $validator = \Validator::make(array (
  'name' => 'Test Co',
), array (
  'name' => 'required|string|max:255',
  'integration_type' => 'nullable|in:api,manual',
  'status' => 'nullable|in:active,inactive,suspended',
));
        $this->assertTrue($validator->passes());
    }

    public function test_missingName_fails_validation(): void
    {
        $validator = \Validator::make(array (
  'integration_type' => 'api',
), array (
  'name' => 'required|string|max:255',
  'integration_type' => 'nullable|in:api,manual',
  'status' => 'nullable|in:active,inactive,suspended',
));
        $this->assertFalse($validator->passes());
    }

    public function test_invalidIntegrationType_fails_validation(): void
    {
        $validator = \Validator::make(array (
  'name' => 'Test',
  'integration_type' => 'bad',
), array (
  'name' => 'required|string|max:255',
  'integration_type' => 'nullable|in:api,manual',
  'status' => 'nullable|in:active,inactive,suspended',
));
        $this->assertFalse($validator->passes());
    }

    public function test_invalidStatus_fails_validation(): void
    {
        $validator = \Validator::make(array (
  'name' => 'Test',
  'status' => 'bad',
), array (
  'name' => 'required|string|max:255',
  'integration_type' => 'nullable|in:api,manual',
  'status' => 'nullable|in:active,inactive,suspended',
));
        $this->assertFalse($validator->passes());
    }

}
