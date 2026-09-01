<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Party;
use App\Models\PurchaseOrder;
use App\Models\SupplierInvoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierInvoiceFactory extends Factory
{
    protected $model = SupplierInvoice::class;

    public function definition(): array
    {
        return [
            'supplier_id' => Party::factory(),
            'business_id' => Business::factory(),
            'branch_id' => null,
            'purchase_id' => null,
            'purchase_order_id' => null,
            'created_by' => null,
            'approved_by' => null,
            'invoice_number' => 'INV-' . date('Y') . '-' . strtoupper(fake()->unique()->bothify('??-#####')),
            'invoice_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'due_date' => fake()->dateTimeBetween('+1 week', '+1 month'),
            'subtotal' => fake()->randomFloat(2, 100, 5000),
            'tax_amount' => fake()->randomFloat(2, 0, 500),
            'discount_amount' => fake()->randomFloat(2, 0, 200),
            'total_amount' => fake()->randomFloat(2, 100, 5000),
            'status' => 'draft',
            'paid_amount' => 0,
            'balance' => 0,
            'currency' => 'SAR',
            'payment_terms' => 'net_30',
            'notes' => fake()->optional()->sentence(),
            'internal_notes' => fake()->optional()->sentence(),
        ];
    }
}
