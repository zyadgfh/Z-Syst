<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\DueCollect;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DueCollectFactory extends Factory
{
    protected $model = DueCollect::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'party_id' => null,
            'user_id' => User::factory(),
            'sale_id' => Sale::factory(),
            'purchase_id' => Purchase::factory(),
            'invoiceNumber' => null,
            'totalDue' => fake()->randomFloat(2, 0, 5000),
            'dueAmountAfterPay' => fake()->randomFloat(2, 0, 5000),
            'payDueAmount' => fake()->randomFloat(2, 0, 5000),
            'paymentType' => fake()->randomElement(['cash', 'card', 'bank_transfer']),
            'paymentDate' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
