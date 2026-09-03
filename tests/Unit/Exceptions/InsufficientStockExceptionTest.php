<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\InsufficientStockException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsufficientStockExceptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_insufficient_stock_has_422_status(): void
    {
        $exception = new InsufficientStockException();

        $this->assertEquals(422, $exception->getStatusCode());
        $this->assertEquals('INSUFFICIENT_STOCK', $exception->getErrorCode());
    }

    public function test_insufficient_stock_has_default_message(): void
    {
        $exception = new InsufficientStockException();

        $this->assertEquals('Insufficient stock available', $exception->getMessage());
    }

    public function test_insufficient_stock_with_custom_message(): void
    {
        $exception = new InsufficientStockException('Not enough tablets');

        $this->assertEquals('Not enough tablets', $exception->getMessage());
    }

    public function test_insufficient_stock_with_errors(): void
    {
        $exception = new InsufficientStockException('Stock issue', ['product_id' => 1, 'requested' => 50, 'available' => 10]);

        $this->assertEquals(['product_id' => 1, 'requested' => 50, 'available' => 10], $exception->getErrors());
    }
}
