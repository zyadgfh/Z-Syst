<?php

namespace Database\Factories;

use App\Models\Manufacturer;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

class ManufacturerFactory extends Factory
{
    protected $model = Manufacturer::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->company(),
            'status' => true,
        ];
    }
}
