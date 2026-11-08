<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoicePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierInvoicePaymentFactory extends Factory
{
    protected $model = SupplierInvoicePayment::class;

    public function definition(): array
    {
        return [
            'payment_number' => 'PAY-'.str_pad(fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'supplier_invoice_id' => SupplierInvoice::factory(),
            'business_id' => Business::factory(),
            'payment_date' => now(),
            'payment_method' => SupplierInvoicePayment::METHOD_CASH,
            'amount' => 100,
            'status' => SupplierInvoicePayment::STATUS_PENDING,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SupplierInvoicePayment::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }
}
