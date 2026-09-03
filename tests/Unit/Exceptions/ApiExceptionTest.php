<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\ApiException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiExceptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_exception_has_default_values(): void
    {
        $exception = new ApiException();

        $this->assertEquals('An error occurred', $exception->getMessage());
        $this->assertEquals(400, $exception->getStatusCode());
        $this->assertEquals('API_ERROR', $exception->getErrorCode());
        $this->assertEquals([], $exception->getErrors());
    }

    public function test_api_exception_with_custom_values(): void
    {
        $exception = new ApiException(
            'Custom error message',
            422,
            'CUSTOM_ERROR',
            ['field' => 'is required']
        );

        $this->assertEquals('Custom error message', $exception->getMessage());
        $this->assertEquals(422, $exception->getStatusCode());
        $this->assertEquals('CUSTOM_ERROR', $exception->getErrorCode());
        $this->assertEquals(['field' => 'is required'], $exception->getErrors());
    }

    public function test_api_exception_renders_json_response(): void
    {
        $exception = new ApiException('Test error', 400, 'TEST_ERROR');

        $response = $exception->render();

        $this->assertEquals(400, $response->getStatusCode());
        
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Test error', $content['message']);
        $this->assertEquals('TEST_ERROR', $content['error_code']);
    }
}
