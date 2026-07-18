<?php

namespace App\Services\Payment\DTOs;

use App\Services\Payment\Enums\TransactionStatus;

/**
 * Webhook Payload DTO
 * 
 * كائن نقل البيانات من استلام Webhook
 */
class WebhookPayloadDTO
{
    public function __construct(
        public readonly string $transactionId,
        public readonly string $orderId,
        public readonly TransactionStatus $status,
        public readonly float $amount,
        public readonly string $currency,
        public readonly ?string $failureReason = null,
        public readonly ?array $raw = null,
    ) {}

    /**
     * Create from Paymob webhook payload
     */
    public static function fromPaymob(array $payload): self
    {
        $obj = $payload['obj'] ?? [];
        
        return new self(
            transactionId: (string) ($obj['id'] ?? $obj['order']['id'] ?? ''),
            orderId: (string) ($obj['order']['id'] ?? ''),
            status: $obj['success'] ?? false 
                ? TransactionStatus::COMPLETED 
                : TransactionStatus::FAILED,
            amount: ($obj['amount_cents'] ?? 0) / 100,
            currency: $obj['currency'] ?? 'EGP',
            failureReason: $obj['data']['message'] ?? null,
            raw: $payload,
        );
    }

    /**
     * Create from generic payload
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionId: (string) ($data['transaction_id'] ?? ''),
            orderId: (string) ($data['order_id'] ?? ''),
            status: TransactionStatus::from($data['status'] ?? 'failed'),
            amount: (float) ($data['amount'] ?? 0),
            currency: $data['currency'] ?? 'EGP',
            failureReason: $data['failure_reason'] ?? null,
            raw: $data,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'order_id' => $this->orderId,
            'status' => $this->status->value,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'failure_reason' => $this->failureReason,
        ];
    }
}