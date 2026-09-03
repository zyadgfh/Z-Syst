<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\ResourceNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceNotFoundExceptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_not_found_has_404_status(): void
    {
        $exception = new ResourceNotFoundException('Product');

        $this->assertEquals(404, $exception->getStatusCode());
        $this->assertEquals('RESOURCE_NOT_FOUND', $exception->getErrorCode());
    }

    public function test_resource_not_found_has_correct_message(): void
    {
        $exception = new ResourceNotFoundException('User');

        $this->assertStringContainsString('User', $exception->getMessage());
    }

    public function test_resource_not_found_with_custom_message(): void
    {
        $exception = new ResourceNotFoundException('Product', 'Custom not found message');

        $this->assertEquals('Custom not found message', $exception->getMessage());
    }
}
