<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoicePayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierInvoicePaymentFactory extends Factory
{
    protected $model = SupplierInvoicePayment::class;

    public function definition(): array
    {
        return [
            'supplier_invoice_id' => SupplierInvoice::factory(),
            'business_id' => Business::factory(),
            'branch_id' => null,
            'created_by' => null,
            'approved_by' => null,
            'payment_number' => 'PAY-' . strtoupper(fake()->unique()->bothify('??-#####')),
            'payment_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'payment_method' => fake()->randomElement(['cash', 'bank_transfer', 'check', 'card']),
            'payment_reference' => fake()->optional()->bothify('??-#####'),
            'bank_reference' => null,
            'amount' => fake()->randomFloat(2, 100, 5000),
            'status' => 'pending',
            'notes' => fake()->optional()->sentence(),
            'approved_at' => null,
            'file_path' => null,
            'file_name' => null,
            'file_mime_type' => null,
        ];
    }
}
