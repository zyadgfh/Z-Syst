<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\NotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotFoundExceptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_not_found_exception_has_correct_values(): void
    {
        $exception = new NotFoundException('Product', ['id' => 42]);

        $this->assertStringContainsString('Product', $exception->getMessage());
        $this->assertEquals('RESOURCE_NOT_FOUND', $exception->errorCode->value);
    }

    public function test_not_found_exception_renders_404_response(): void
    {
        $exception = new NotFoundException('Sale', ['id' => 99]);

        $response = $exception->render(request());

        $this->assertEquals(404, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertStringContainsString('Sale', $content['message']);
        $this->assertEquals('RESOURCE_NOT_FOUND', $content['error_code']);
    }

    public function test_not_found_exception_with_default_message(): void
    {
        $exception = new NotFoundException('Order');

        $this->assertStringContainsString('Order', $exception->getMessage());
    }
}
