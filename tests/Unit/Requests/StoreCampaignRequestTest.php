<?php

namespace Tests\Unit\Requests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreCampaignRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_email_data_passes_validation(): void
    {
        $validator = \Validator::make(array (
  'name' => 'Welcome',
  'type' => 'email',
  'subject' => 'Hi',
  'content' => 'Body',
), array (
  'name' => 'required|string|max:255',
  'type' => 'required|in:email,sms,push',
  'subject' => 'required_if:type,email|nullable|string|max:255',
  'content' => 'required|string',
  'target_segment' => 'nullable|in:all,active,inactive,high_value,due_balance',
));
        $this->assertTrue($validator->passes());
    }

    public function test_valid_sms_data_passes_validation(): void
    {
        $validator = \Validator::make(array (
  'name' => 'Promo',
  'type' => 'sms',
  'content' => 'Body',
), array (
  'name' => 'required|string|max:255',
  'type' => 'required|in:email,sms,push',
  'subject' => 'required_if:type,email|nullable|string|max:255',
  'content' => 'required|string',
  'target_segment' => 'nullable|in:all,active,inactive,high_value,due_balance',
));
        $this->assertTrue($validator->passes());
    }

    public function test_missingName_fails_validation(): void
    {
        $validator = \Validator::make(array (
  'type' => 'email',
  'subject' => 'Hi',
  'content' => 'Body',
), array (
  'name' => 'required|string|max:255',
  'type' => 'required|in:email,sms,push',
  'subject' => 'required_if:type,email|nullable|string|max:255',
  'content' => 'required|string',
  'target_segment' => 'nullable|in:all,active,inactive,high_value,due_balance',
));
        $this->assertFalse($validator->passes());
    }

    public function test_missingType_fails_validation(): void
    {
        $validator = \Validator::make(array (
  'name' => 'Test',
  'subject' => 'Hi',
  'content' => 'Body',
), array (
  'name' => 'required|string|max:255',
  'type' => 'required|in:email,sms,push',
  'subject' => 'required_if:type,email|nullable|string|max:255',
  'content' => 'required|string',
  'target_segment' => 'nullable|in:all,active,inactive,high_value,due_balance',
));
        $this->assertFalse($validator->passes());
    }

    public function test_invalidType_fails_validation(): void
    {
        $validator = \Validator::make(array (
  'name' => 'Test',
  'type' => 'bad',
  'subject' => 'Hi',
  'content' => 'Body',
), array (
  'name' => 'required|string|max:255',
  'type' => 'required|in:email,sms,push',
  'subject' => 'required_if:type,email|nullable|string|max:255',
  'content' => 'required|string',
  'target_segment' => 'nullable|in:all,active,inactive,high_value,due_balance',
));
        $this->assertFalse($validator->passes());
    }

    public function test_emailRequiresSubject_fails_validation(): void
    {
        $validator = \Validator::make(array (
  'name' => 'Test',
  'type' => 'email',
  'content' => 'Body',
), array (
  'name' => 'required|string|max:255',
  'type' => 'required|in:email,sms,push',
  'subject' => 'required_if:type,email|nullable|string|max:255',
  'content' => 'required|string',
  'target_segment' => 'nullable|in:all,active,inactive,high_value,due_balance',
));
        $this->assertFalse($validator->passes());
    }

    public function test_invalidTargetSegment_fails_validation(): void
    {
        $validator = \Validator::make(array (
  'name' => 'Test',
  'type' => 'email',
  'subject' => 'Hi',
  'content' => 'Body',
  'target_segment' => 'invalid',
), array (
  'name' => 'required|string|max:255',
  'type' => 'required|in:email,sms,push',
  'subject' => 'required_if:type,email|nullable|string|max:255',
  'content' => 'required|string',
  'target_segment' => 'nullable|in:all,active,inactive,high_value,due_balance',
));
        $this->assertFalse($validator->passes());
    }

}
