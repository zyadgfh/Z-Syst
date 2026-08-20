<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\StockUnavailableException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockUnavailableExceptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_unavailable_has_400_status(): void
    {
        $exception = new StockUnavailableException();

        $this->assertEquals(400, $exception->getCode());
    }

    public function test_stock_unavailable_has_arabic_default_message(): void
    {
        $exception = new StockUnavailableException();

        $this->assertStringContainsString('المخزون', $exception->getMessage());
    }

    public function test_stock_unavailable_with_custom_message(): void
    {
        $exception = new StockUnavailableException('No stock available for this product');

        $this->assertEquals('No stock available for this product', $exception->getMessage());
    }
}
