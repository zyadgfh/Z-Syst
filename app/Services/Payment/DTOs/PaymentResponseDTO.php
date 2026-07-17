<?php

namespace App\Services\Payment\DTOs;

use App\Services\Payment\Enums\TransactionStatus;

/**
 * Payment Response DTO
 * 
 * كائن نقل البيانات للرد من بوابة الدفع
 */
class PaymentResponseDTO
{
    public function __construct(
        public readonly string $transactionId,
        public readonly string $orderId,
        public readonly TransactionStatus $status,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $paymentUrl = null,
        public readonly ?string $qrCodeUrl = null,
        public readonly ?string $qrCodeData = null,
        public readonly ?float $amount = null,
        public readonly ?string $currency = null,
        public readonly ?array $raw = null,
        public readonly ?string $failureReason = null,
    ) {}

    /**
     * Create from PaymentTransaction model
     */
    public static function fromPayment(object $payment): self
    {
        return new self(
            transactionId: $payment->external_transaction_id ?? $payment->id,
            orderId: $payment->external_reference ?? $payment->id,
            status: TransactionStatus::from($payment->status),
            paymentUrl: $payment->payment_url,
            qrCodeUrl: $payment->qr_code_url,
            qrCodeData: $payment->qr_code_data,
            amount: $payment->amount,
            currency: $payment->currency,
            raw: $payment->gateway_response ?? $payment->toArray(),
            failureReason: $payment->failure_reason,
        );
    }

    /**
     * Convert to array for API response
     */
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'order_id' => $this->orderId,
            'status' => $this->status->value,
            'redirect_url' => $this->redirectUrl,
            'payment_url' => $this->paymentUrl,
            'qr_code_url' => $this->qrCodeUrl,
            'qr_code_data' => $this->qrCodeData,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'failure_reason' => $this->failureReason,
        ];
    }
}