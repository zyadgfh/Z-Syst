<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Party;
use App\Models\SupplierInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierInvoiceFactory extends Factory
{
    protected $model = SupplierInvoice::class;

    public function definition(): array
    {
        return [
            // invoice_number, invoice_date and due_date are filled by the
            // model's creating hook when left empty.
            'supplier_id' => Party::factory()->state(['type' => 'supplier']),
            'business_id' => Business::factory(),
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => 1000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'status' => SupplierInvoice::STATUS_PENDING,
            'paid_amount' => 0,
            'balance' => 1000,
            'currency' => 'SAR',
            'payment_terms' => 'net_30',
            'is_active' => true,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SupplierInvoice::STATUS_APPROVED,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SupplierInvoice::STATUS_PAID,
            'paid_amount' => $attributes['total_amount'] ?? 1000,
            'balance' => 0,
        ]);
    }
}
