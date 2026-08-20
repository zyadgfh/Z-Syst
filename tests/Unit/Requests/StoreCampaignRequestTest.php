<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreCampaignRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreCampaignRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'business_id' => 1,
        ]);
        
        $this->actingAs($this->user);
    }

    public function test_valid_email_campaign_data_passes(): void
    {
        $request = new StoreCampaignRequest();
        $request->merge([
            'name' => 'Welcome Email',
            'type' => 'email',
            'subject' => 'Welcome to our store',
            'content' => 'Thank you for joining us!',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertTrue($request->passesAuthorization());
    }

    public function test_valid_sms_campaign_passes_without_subject(): void
    {
        $request = new StoreCampaignRequest();
        $request->merge([
            'name' => 'SMS Promotion',
            'type' => 'sms',
            'content' => 'Get 20% off today only!',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertTrue($request->passesAuthorization());
    }

    public function test_missing_name_fails(): void
    {
        $request = new StoreCampaignRequest();
        $request->merge([
            'type' => 'email',
            'subject' => 'Test',
            'content' => 'Test content',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertFalse($request->passesAuthorization());
    }

    public function test_missing_type_fails(): void
    {
        $request = new StoreCampaignRequest();
        $request->merge([
            'name' => 'Test Campaign',
            'subject' => 'Test',
            'content' => 'Test content',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertFalse($request->passesAuthorization());
    }

    public function test_invalid_type_fails(): void
    {
        $request = new StoreCampaignRequest();
        $request->merge([
            'name' => 'Test Campaign',
            'type' => 'invalid_type',
            'subject' => 'Test',
            'content' => 'Test content',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertFalse($request->passesAuthorization());
    }

    public function test_email_campaign_requires_subject(): void
    {
        $request = new StoreCampaignRequest();
        $request->merge([
            'name' => 'Test Campaign',
            'type' => 'email',
            'content' => 'Test content',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertFalse($request->passesAuthorization());
    }

    public function test_invalid_target_segment_fails(): void
    {
        $request = new StoreCampaignRequest();
        $request->merge([
            'name' => 'Test Campaign',
            'type' => 'email',
            'subject' => 'Test',
            'content' => 'Test content',
            'target_segment' => 'invalid_segment',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertFalse($request->passesAuthorization());
    }
}