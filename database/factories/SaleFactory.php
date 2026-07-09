<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        return [
            'invoice_number' => $this->faker->unique()->bothify('INV-##########'),
            'customer_id' => null,
            'branch_id' => Branch::factory(),
            'user_id' => User::factory(),
            'subtotal' => $this->faker->randomFloat(2, 10, 1000),
            'discount_amount' => $this->faker->randomFloat(2, 0, 50),
            'tax_amount' => $this->faker->randomFloat(2, 0, 20),
            'total_amount' => $this->faker->randomFloat(2, 10, 1000),
            'amount_paid' => $this->faker->randomFloat(2, 10, 1000),
            'change_amount' => $this->faker->randomFloat(2, 0, 50),
            'payment_method' => $this->faker->randomElement(['cash', 'card', 'insurance']),
            'payment_status' => $this->faker->randomElement(['paid', 'partial', 'pending']),
            'sale_type' => $this->faker->randomElement(['walk-in', 'prescription', 'insurance']),
            'prescription_id' => null,
            'notes' => $this->faker->sentence(),
            'company_id' => Company::factory(),
        ];
    }
}
