<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Invoice;
use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 10000);
        $taxAmount = fake()->randomFloat(2, 0, 500);

        return [
            'business_id' => Business::factory(),
            'branch_id' => null,
            'sale_id' => null,
            'party_id' => Party::factory(),
            'tax_id' => null,
            'invoice_number' => 'INV-' . fake()->unique()->numerify('######'),
            'invoice_date' => fake()->date(),
            'due_date' => fake()->dateTimeBetween('+1 week', '+1 month'),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => fake()->randomFloat(2, 0, 200),
            'total_amount' => $subtotal + $taxAmount,
            'paid_amount' => fake()->randomFloat(2, 0, $subtotal + $taxAmount),
            'balance' => fake()->randomFloat(2, 0, 1000),
            'status' => fake()->randomElement(['draft', 'sent', 'paid', 'overdue', 'canceled']),
            'payment_method' => fake()->randomElement(['cash', 'card', 'bank_transfer', 'credit']),
            'notes' => fake()->optional()->text(),
            'is_proforma' => fake()->boolean(10),
            'qr_code' => null,
            'qr_code_data' => null,
            'einvoice_status' => null,
            'einvoice_submission_id' => null,
            'einvoice_submitted_at' => null,
            'einvoice_compliance_hash' => null,
            'is_einvoice_compliant' => false,
        ];
    }

    public function paid(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_amount' => $attributes['total_amount'],
            'balance' => 0,
        ]);
    }

    public function overdue(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'due_date' => fake()->dateTimeBetween('-2 months', '-1 week'),
        ]);
    }

    public function draft(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }
}
