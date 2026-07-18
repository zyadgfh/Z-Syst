<?php

namespace App\Services\Payment\Exceptions;

use Exception;

class PaymentException extends Exception
{
    protected array $errors = [];
    protected ?string $gatewayName = null;

    public function __construct(
        string $message = 'Payment processing error occurred.',
        int $code = 400,
        ?\Throwable $previous = null,
        ?string $gatewayName = null,
        array $errors = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->gatewayName = $gatewayName;
        $this->errors = $errors;
    }

    public function getGatewayName(): ?string
    {
        return $this->gatewayName;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function toArray(): array
    {
        return [
            'success' => false,
            'message' => $this->getMessage(),
            'gateway' => $this->gatewayName,
            'errors' => $this->errors,
            'code' => $this->getCode(),
        ];
    }

    public static function gatewayNotAvailable(?string $gateway = null): self
    {
        return new self(
            message: "Payment gateway '{$gateway}' is not available or not configured.",
            code: 503,
            gatewayName: $gateway,
        );
    }

    public static function invalidAmount(?string $gateway = null): self
    {
        return new self(
            message: 'Invalid payment amount provided.',
            code: 422,
            gatewayName: $gateway,
        );
    }

    public static function transactionNotFound(?string $gateway = null): self
    {
        return new self(
            message: 'Transaction not found or has expired.',
            code: 404,
            gatewayName: $gateway,
        );
    }

    public static function verificationFailed(?string $gateway = null, ?string $reason = null): self
    {
        return new self(
            message: $reason ?? 'Payment verification failed.',
            code: 400,
            gatewayName: $gateway,
        );
    }

    public static function refundFailed(?string $gateway = null, ?string $reason = null): self
    {
        return new self(
            message: $reason ?? 'Refund processing failed.',
            code: 400,
            gatewayName: $gateway,
        );
    }

    public static function invalidConfiguration(?string $gateway = null): self
    {
        return new self(
            message: "Payment gateway '{$gateway}' is not properly configured.",
            code: 500,
            gatewayName: $gateway,
        );
    }

    public static function webhookVerificationFailed(?string $gateway = null): self
    {
        return new self(
            message: 'Webhook signature verification failed.',
            code: 401,
            gatewayName: $gateway,
        );
    }

    public static function authenticationFailed(?string $gateway = null): self
    {
        return new self(
            message: 'Authentication failed with payment gateway.',
            code: 500,
            gatewayName: $gateway,
        );
    }

    public static function initiationFailed(?string $gateway = null, $data = null): self
    {
        $reason = $data['message'] ?? 'Payment initiation failed.';
        return new self(
            message: $reason,
            code: 400,
            gatewayName: $gateway,
        );
    }

    public static function invalidTransaction(?string $reason = null): self
    {
        return new self(
            message: $reason ?? 'Invalid transaction.',
            code: 400,
        );
    }

    public static function cannotRefund(?string $gateway = null, ?string $reason = null): self
    {
        return new self(
            message: $reason ?? 'Cannot process refund for this transaction.',
            code: 400,
            gatewayName: $gateway,
        );
    }
}
