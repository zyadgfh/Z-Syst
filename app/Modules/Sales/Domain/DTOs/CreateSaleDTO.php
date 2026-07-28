<?php

declare(strict_types=1);

namespace App\Modules\Sales\Domain\DTOs;

/**
 * Create Sale Data Transfer Object
 *
 * Carries validated data for creating a new sale.
 * Supports items array with product_id, quantity, unit_price, discount, and tax.
 */
class CreateSaleDTO
{
    /**
     * @param array<array{product_id: string, quantity: float, unit_price: float, discount?: float, tax?: float}> $items
     * @param array<array{payment_method: string, amount: float, reference_number?: string, transaction_id?: string}>|null $payments
     */
    public function __construct(
        public readonly array $items,
        public readonly ?string $customer_id = null,
        public readonly ?string $customer_name = null,
        public readonly ?string $customer_phone = null,
        public readonly ?string $branch_id = null,
        public readonly ?string $company_id = null,
        public readonly ?string $payment_method = 'cash',
        public readonly ?float $amount_paid = null,
        public readonly ?float $discount_amount = 0,
        public readonly ?float $tax_amount = 0,
        public readonly ?float $tax_rate = 0,
        public readonly ?string $notes = null,
        public readonly ?string $created_by = null,
        public readonly ?string $prescription_id = null,
        public readonly ?array $payments = null,
        public readonly ?string $cash_register_id = null,
        public readonly ?string $insurance_claim_id = null,
        public readonly ?array $wallet_data = null,
    ) {}

    /**
     * Create from validated request array.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            items: $data['items'],
            customer_id: $data['customer_id'] ?? null,
            customer_name: $data['customer_name'] ?? null,
            customer_phone: $data['customer_phone'] ?? null,
            branch_id: $data['branch_id'] ?? null,
            company_id: $data['company_id'] ?? null,
            payment_method: $data['payment_method'] ?? 'cash',
            amount_paid: isset($data['amount_paid']) ? (float) $data['amount_paid'] : null,
            discount_amount: isset($data['discount_amount']) ? (float) $data['discount_amount'] : 0,
            tax_amount: isset($data['tax_amount']) ? (float) $data['tax_amount'] : 0,
            tax_rate: isset($data['tax_rate']) ? (float) $data['tax_rate'] : 0,
            notes: $data['notes'] ?? null,
            created_by: $data['created_by'] ?? null,
            prescription_id: $data['prescription_id'] ?? null,
            payments: $data['payments'] ?? null,
            cash_register_id: $data['cash_register_id'] ?? null,
            insurance_claim_id: $data['insurance_claim_id'] ?? null,
            wallet_data: $data['wallet_data'] ?? null,
        );
    }

    /**
     * Convert to array for model creation.
     */
    public function toArray(): array
    {
        return array_filter([
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'branch_id' => $this->branch_id,
            'company_id' => $this->company_id,
            'payment_method' => $this->payment_method,
            'amount_paid' => $this->amount_paid,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'tax_rate' => $this->tax_rate,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'prescription_id' => $this->prescription_id,
        ], fn ($value) => $value !== null);
    }
}

