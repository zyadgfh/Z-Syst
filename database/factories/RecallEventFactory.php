<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Product;
use App\Models\RecallEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecallEventFactory extends Factory
{
    protected $model = RecallEvent::class;

    public function definition()
    {
        return [
            'business_id' => Business::factory(),
            'product_id' => Product::factory(),
            'batch_lot_number' => fake()->optional()->regexify('[A-Z0-9]{10}'),
            'reason' => fake()->randomElement([
                'Quality concern',
                'Manufacturing defect',
                'Contamination risk',
                'Labeling error',
                'Regulatory requirement',
            ]),
            'initiated_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'resolved_at' => fake()->optional(0.6)->dateTimeBetween('-3 months', 'now'),
            'status' => fake()->randomElement(['active', 'resolved']),
            'description' => fake()->optional()->paragraph(),
            'user_id' => null,
        ];
    }
}
