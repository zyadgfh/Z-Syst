<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\UnauthorizedAccessException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnauthorizedAccessExceptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized_access_has_403_status(): void
    {
        $exception = new UnauthorizedAccessException();

        $this->assertEquals(403, $exception->getStatusCode());
        $this->assertEquals('UNAUTHORIZED_ACCESS', $exception->getErrorCode());
    }

    public function test_unauthorized_access_has_default_message(): void
    {
        $exception = new UnauthorizedAccessException();

        $this->assertEquals('You do not have permission to perform this action', $exception->getMessage());
    }

    public function test_unauthorized_access_with_custom_message(): void
    {
        $exception = new UnauthorizedAccessException('Access denied to admin panel');

        $this->assertEquals('Access denied to admin panel', $exception->getMessage());
    }
}
