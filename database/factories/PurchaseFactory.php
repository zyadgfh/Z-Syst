<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\Party;
use App\Models\Business;
use App\Models\User;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    public function definition(): array
    {
        $totalAmount = fake()->randomFloat(2, 50, 5000);
        $paidAmount = fake()->randomFloat(2, 0, $totalAmount);
        $dueAmount = $totalAmount - $paidAmount;

        return [
            'party_id' => Party::factory(),
            'business_id' => Business::factory(),
            'user_id' => User::factory(),
            'totalAmount' => $totalAmount,
            'paidAmount' => $paidAmount,
            'dueAmount' => $dueAmount,
            'discountAmount' => fake()->randomFloat(2, 0, 100),
            'isPaid' => $dueAmount == 0,
            'paymentType' => fake()->randomElement(['cash', 'credit']),
            'purchaseDate' => fake()->dateTimeThisYear()->format('Y-m-d'),
            'invoiceNumber' => 'P-' . str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
        ];
    }
}