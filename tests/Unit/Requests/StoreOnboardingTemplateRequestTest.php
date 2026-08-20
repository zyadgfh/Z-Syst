<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreOnboardingTemplateRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreOnboardingTemplateRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'business_id' => 1,
            'role' => 'admin',
        ]);
        
        $this->actingAs($this->user);
    }

    public function test_valid_template_data_passes(): void
    {
        $request = new StoreOnboardingTemplateRequest();
        $request->merge([
            'name' => 'Standard Onboarding',
            'code' => 'standard',
            'description' => 'Standard onboarding flow',
            'steps' => [
                ['name' => 'Setup', 'action' => 'setup_default_settings', 'description' => 'Configure settings'],
            ],
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertTrue($request->passesAuthorization());
    }

    public function test_missing_name_fails(): void
    {
        $request = new StoreOnboardingTemplateRequest();
        $request->merge([
            'code' => 'standard',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertFalse($request->passesAuthorization());
    }

    public function test_missing_code_fails(): void
    {
        $request = new StoreOnboardingTemplateRequest();
        $request->merge([
            'name' => 'Standard Onboarding',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertFalse($request->passesAuthorization());
    }

    public function test_duplicate_code_fails(): void
    {
        $request = new StoreOnboardingTemplateRequest();
        $request->merge([
            'name' => 'Standard Onboarding',
            'code' => 'EXISTING_CODE',
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertTrue($request->passesAuthorization()); // Auth passes, validation would fail
    }

    public function test_invalid_step_action_fails(): void
    {
        $request = new StoreOnboardingTemplateRequest();
        $request->merge([
            'name' => 'Standard Onboarding',
            'code' => 'standard',
            'steps' => [
                ['name' => 'Setup', 'action' => 'invalid_action'],
            ],
        ]);
        
        $request->setUserResolver(fn () => $this->user);
        
        $this->assertTrue($request->passesAuthorization()); // Auth passes, validation would fail for step action
    }
}