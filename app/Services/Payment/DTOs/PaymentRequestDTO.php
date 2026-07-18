<?php

namespace App\Services\Payment\DTOs;

use App\Services\Payment\Enums\PaymentMethodType;

/**
 * Payment Request DTO
 * 
 * كائن نقل البيانات لبدء عملية الدفع
 */
class PaymentRequestDTO
{
    public function __construct(
        public readonly ?int $companyId,
        public readonly PaymentMethodType $paymentMethod,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $merchantReference,
        public readonly ?string $payableType = null,
        public readonly ?int $payableId = null,
        public readonly ?int $customerId = null,
        public readonly ?int $userId = null,
        public readonly ?int $branchId = null,
        public readonly ?array $paymentMethodDetails = null,
        public readonly ?string $description = null,
        public readonly ?string $callbackUrl = null,
        public readonly ?array $metadata = null,
    ) {}

    /**
     * Create from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            companyId: $data['company_id'] ?? null,
            paymentMethod: PaymentMethodType::from($data['payment_method_type']),
            amount: (float) ($data['amount'] ?? 0),
            currency: $data['currency'] ?? 'EGP',
            merchantReference: $data['merchant_reference'] ?? uniqid('payment_'),
            payableType: $data['payable_type'] ?? null,
            payableId: $data['payable_id'] ?? null,
            customerId: $data['customer_id'] ?? null,
            userId: $data['user_id'] ?? auth()->id(),
            branchId: $data['branch_id'] ?? null,
            paymentMethodDetails: $data['payment_method_details'] ?? null,
            description: $data['description'] ?? null,
            callbackUrl: $data['callback_url'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Create from sale data
     */
    public static function fromSale(
        object $sale,
        array $data
    ): self {
        return new self(
            companyId: $sale->company_id ?? null,
            paymentMethod: PaymentMethodType::from($data['payment_method_type']),
            amount: $sale->total_amount,
            currency: $sale->currency ?? 'EGP',
            merchantReference: $data['merchant_reference'] ?? uniqid('payment_'),
            payableType: get_class($sale),
            payableId: $sale->id,
            userId: $data['user_id'] ?? null,
            branchId: $sale->branch_id ?? null,
            paymentMethodDetails: $data['payment_method_details'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Convert to array for gateway processing
     */
    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'payment_method_type' => $this->paymentMethod->value,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'merchant_reference' => $this->merchantReference,
            'reference_id' => $this->payableId,
            'reference_type' => $this->payableType,
            'customer_id' => $this->customerId,
            'user_id' => $this->userId,
            'branch_id' => $this->branchId,
            'mobile_number' => $this->paymentMethodDetails['phone_number'] ?? null,
            'description' => $this->description,
            'callback_url' => $this->callbackUrl,
            'metadata' => $this->metadata,
        ];
    }
}