<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'branch_id' => Branch::factory(),
            'uuid' => fake()->uuid(),
            'status' => 'pending',
            'total' => fake()->randomFloat(2, 10, 1000),
            'currency' => 'USD',
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->phoneNumber(),
            'notes' => fake()->optional()->sentence(),
            'created_by' => 1,
        ];
    }
}
