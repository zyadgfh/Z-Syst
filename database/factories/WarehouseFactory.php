<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition()
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->words(3, true),
            'code' => 'WH-'.strtoupper(fake()->unique()->regexify('[A-Z0-9]{8}')),
            'location' => fake()->address(),
            'is_default' => false,
            'is_active' => true,
        ];
    }
}
