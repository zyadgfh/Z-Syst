<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\PaymentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentExceptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_exception_has_correct_message(): void
    {
        $exception = PaymentException::gatewayNotFound('stripe');

        $this->assertStringContainsString('stripe', $exception->getMessage());
        $this->assertStringContainsString('Unsupported gateway type', $exception->getMessage());
    }

    public function test_gateway_not_active(): void
    {
        $exception = PaymentException::gatewayNotActive();

        $this->assertStringContainsString('not active', $exception->getMessage());
    }

    public function test_no_available_gateways(): void
    {
        $exception = PaymentException::noAvailableGateways();

        $this->assertStringContainsString('No active', $exception->getMessage());
    }

    public function test_transaction_not_found(): void
    {
        $exception = PaymentException::transactionNotFound(42);

        $this->assertStringContainsString('42', $exception->getMessage());
        $this->assertStringContainsString('Transaction not found', $exception->getMessage());
    }

    public function test_invalid_transaction_status(): void
    {
        $exception = PaymentException::invalidTransactionStatus('completed', 'pending');

        $this->assertStringContainsString('completed', $exception->getMessage());
        $this->assertStringContainsString('pending', $exception->getMessage());
    }

    public function test_payment_processing_failed(): void
    {
        $exception = PaymentException::paymentProcessingFailed('Card declined');

        $this->assertStringContainsString('Card declined', $exception->getMessage());
    }

    public function test_refund_failed(): void
    {
        $exception = PaymentException::refundFailed('Insufficient funds');

        $this->assertStringContainsString('Insufficient funds', $exception->getMessage());
    }
}
