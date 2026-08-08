<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Warehouse;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition()
    {
        return [
            'business_id' => \App\Models\Business::factory(),
            'name' => fake()->words(3, true),
            'code' => 'WH-' . strtoupper(fake()->unique()->regexify('[A-Z0-9]{8}')),
            'location' => fake()->address(),
            'is_default' => false,
            'is_active' => true,
        ];
    }
}
