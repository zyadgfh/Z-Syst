<?php

namespace App\Services\Payment\DTOs;

/**
 * Refund Request DTO
 * 
 * كائن نقل البيانات لطلب استرداد مبلغ
 */
class RefundRequestDTO
{
    public function __construct(
        public readonly string $paymentUuid,
        public readonly ?float $amount = null,
        public readonly ?string $reason = null,
        public readonly ?int $requestedBy = null,
        public readonly ?array $metadata = null,
    ) {}

    /**
     * Create from request data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            paymentUuid: $data['payment_uuid'],
            amount: $data['amount'] ?? null,
            reason: $data['reason'] ?? null,
            requestedBy: $data['requested_by'] ?? auth()->id(),
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Convert to array for gateway processing
     */
    public function toArray(): array
    {
        return [
            'payment_uuid' => $this->paymentUuid,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'requested_by' => $this->requestedBy,
            'metadata' => $this->metadata,
        ];
    }
}