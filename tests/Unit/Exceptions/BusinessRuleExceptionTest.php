<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\BusinessRuleException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessRuleExceptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_rule_exception_has_correct_values(): void
    {
        $exception = new BusinessRuleException(
            'INSUFFICIENT_PERMISSION',
            'You need admin access',
            ['required_role' => 'admin']
        );

        $this->assertEquals('INSUFFICIENT_PERMISSION', $exception->getErrorCode());
        $this->assertEquals('You need admin access', $exception->getMessage());
        $this->assertEquals(['required_role' => 'admin'], $exception->getContext());
    }

    public function test_business_rule_exception_renders_422_response(): void
    {
        $exception = new BusinessRuleException('VALIDATION_ERROR', 'Invalid data');

        $response = $exception->render(request());

        $this->assertEquals(422, $response->getStatusCode());
        
        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals('Invalid data', $content['message']);
        $this->assertEquals('VALIDATION_ERROR', $content['error_code']);
    }

    public function test_business_rule_exception_hides_context_in_production(): void
    {
        config(['app.debug' => false]);

        $exception = new BusinessRuleException('ERROR', 'Error', ['secret' => 'data']);

        $response = $exception->render(request());
        $content = json_decode($response->getContent(), true);

        $this->assertEmpty($content['context']);
    }
}
