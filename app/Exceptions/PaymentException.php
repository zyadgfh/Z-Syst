<?php

namespace App\Exceptions;

use Exception;

class PaymentException extends Exception
{
    public static function gatewayNotFound(string $gatewayType): self
    {
        return new self("Unsupported gateway type: {$gatewayType}");
    }

    public static function gatewayNotActive(): self
    {
        return new self('Payment gateway is not active');
    }

    public static function noAvailableGateways(): self
    {
        return new self('No active payment gateways available');
    }

    public static function transactionNotFound(int $transactionId): self
    {
        return new self("Transaction not found: {$transactionId}");
    }

    public static function invalidTransactionStatus(string $expectedStatus, string $actualStatus): self
    {
        return new self("Invalid transaction status. Expected: {$expectedStatus}, Actual: {$actualStatus}");
    }

    public static function invalidConfiguration(string $reason): self
    {
        return new self("Invalid gateway configuration: {$reason}");
    }

    public static function paymentProcessingFailed(string $reason): self
    {
        return new self("Payment processing failed: {$reason}");
    }

    public static function refundFailed(string $reason): self
    {
        return new self("Refund failed: {$reason}");
    }
}
