<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Party;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'supplier_id' => Party::factory(),
            'created_by' => User::factory(),
            'po_number' => 'PO-' . date('Y') . '-' . str_pad(fake()->unique()->numberBetween(1, 99999), 6, '0', STR_PAD_LEFT),
            'status' => fake()->randomElement(['draft', 'pending', 'approved', 'received', 'cancelled']),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'expected_delivery_date' => fake()->dateTimeBetween('+1 week', '+1 month'),
            'subtotal' => fake()->randomFloat(2, 100, 50000),
            'tax_amount' => fake()->randomFloat(2, 0, 5000),
            'discount_amount' => fake()->randomFloat(2, 0, 1000),
            'total_amount' => fake()->randomFloat(2, 100, 50000),
            'terms' => fake()->optional()->sentence(),
            'internal_notes' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
