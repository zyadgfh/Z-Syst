<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Party;
use App\Models\Purchase;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;

class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    public function definition(): array
    {
        return array_filter([
            'party_id' => Party::factory(),
            'company_id' => Schema::hasColumn('purchases', 'company_id') ? Company::factory() : null,
            'business_id' => Schema::hasColumn('purchases', 'business_id') ? Company::factory() : null,
            'user_id' => User::factory(),
            'tax_id' => Tax::factory(),
            'discountAmount' => $this->faker->randomFloat(2, 0, 100),
            'tax_amount' => $this->faker->randomFloat(2, 0, 100),
            'dueAmount' => $this->faker->randomFloat(2, 0, 100),
            'paidAmount' => $this->faker->randomFloat(2, 0, 100),
            'totalAmount' => $this->faker->randomFloat(2, 100, 2000),
            'invoiceNumber' => 'P-'.strtoupper($this->faker->bothify('????####')),
            'isPaid' => $this->faker->boolean(70),
            'paymentType' => $this->faker->randomElement(['Cash', 'Card', 'Bank Transfer']),
            'purchaseDate' => $this->faker->dateTimeBetween('-60 days', 'now'),
            'purchase_data' => ['notes' => $this->faker->sentence()],
            'note' => $this->faker->optional()->sentence(),
        ]);
    }
}
