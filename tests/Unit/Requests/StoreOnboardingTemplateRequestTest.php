<?php

namespace Tests\Unit\Requests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreOnboardingTemplateRequestTest extends TestCase
{
    use RefreshDatabase;

    private array $rules = [
        'name' => 'required|string|max:255',
        'code' => 'required|string|max:100',
        'steps' => 'required|array|min:1',
        'steps.*.action' => 'required|in:create_business,create_branch,invite_users,setup_products,configure_settings',
    ];

    public function test_valid_data_passes_validation(): void
    {
        $validator = Validator::make([
            'name' => 'Template',
            'code' => 'tpl1',
            'steps' => [['action' => 'create_business']],
        ], $this->rules);

        $this->assertTrue($validator->passes());
    }

    public function test_missing_name_fails_validation(): void
    {
        $validator = Validator::make([
            'code' => 'tpl1',
            'steps' => [['action' => 'create_business']],
        ], $this->rules);

        $this->assertFalse($validator->passes());
    }

    public function test_missing_code_fails_validation(): void
    {
        $validator = Validator::make([
            'name' => 'Template',
            'steps' => [['action' => 'create_business']],
        ], $this->rules);

        $this->assertFalse($validator->passes());
    }

    public function test_invalid_step_action_fails_validation(): void
    {
        $validator = Validator::make([
            'name' => 'Template',
            'code' => 'tpl2',
            'steps' => [['action' => 'bad_action']],
        ], $this->rules);

        $this->assertFalse($validator->passes());
    }

    public function test_empty_steps_fails_validation(): void
    {
        $validator = Validator::make([
            'name' => 'Template',
            'code' => 'tpl3',
            'steps' => [],
        ], $this->rules);

        $this->assertFalse($validator->passes());
    }
}
