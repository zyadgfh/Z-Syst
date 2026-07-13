<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $price = fake()->randomFloat(2, 1, 200);

        return [
            'order_id' => Order::factory(),
            'company_id' => Company::factory(),
            'product_id' => Product::factory(),
            'drug_id' => null,
            'quantity' => $quantity,
            'price' => $price,
        ];
    }
}
