<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\GRN;
use App\Models\Party;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GRNFactory extends Factory
{
    protected $model = GRN::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'purchase_order_id' => PurchaseOrder::factory(),
            'supplier_id' => Party::factory(),
            'received_by' => User::factory(),
            'grn_number' => 'GRN-' . fake()->numerify('#####'),
            'received_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'location' => fake()->optional()->city(),
            'status' => fake()->randomElement(['pending', 'verified', 'completed', 'cancelled']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
