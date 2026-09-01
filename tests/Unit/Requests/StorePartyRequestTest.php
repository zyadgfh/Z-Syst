<?php

namespace Tests\Unit\Requests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StorePartyRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_data_passes_validation(): void
    {
        $validator = \Validator::make(array (
  'name' => 'John',
  'partyType' => 'customer',
), array (
  'name' => 'required|string|max:255',
  'partyType' => 'required|in:customer,supplier',
));
        $this->assertTrue($validator->passes());
    }

    public function test_missingName_fails_validation(): void
    {
        $validator = \Validator::make(array (
  'partyType' => 'customer',
), array (
  'name' => 'required|string|max:255',
  'partyType' => 'required|in:customer,supplier',
));
        $this->assertFalse($validator->passes());
    }

    public function test_missingType_fails_validation(): void
    {
        $validator = \Validator::make(array (
  'name' => 'John',
), array (
  'name' => 'required|string|max:255',
  'partyType' => 'required|in:customer,supplier',
));
        $this->assertFalse($validator->passes());
    }

}
