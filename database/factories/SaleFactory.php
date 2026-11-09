<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Party;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        $totalAmount = fake()->randomFloat(2, 50, 5000);
        $paidAmount = fake()->randomFloat(2, 0, $totalAmount);
        $dueAmount = $totalAmount - $paidAmount;

        return [
            'party_id' => Party::factory(),
            'business_id' => Business::factory(),
            'user_id' => User::factory(),
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'due_amount' => $dueAmount,
            'discount_amount' => fake()->randomFloat(2, 0, 100),
            'is_paid' => $dueAmount == 0,
            'payment_type' => fake()->randomElement(['cash', 'card', 'credit']),
            'sale_date' => fake()->dateTimeThisYear()->format('Y-m-d'),
            'invoice_number' => 'S-'.str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
        ];
    }

    public function withBusinessId(int $businessId): static
    {
        return $this->state(fn (array $attributes) => [
            'business_id' => $businessId,
            'party_id' => Party::factory()->withBusinessId($businessId),
            'user_id' => User::factory()->withBusinessId($businessId),
        ]);
    }
}
