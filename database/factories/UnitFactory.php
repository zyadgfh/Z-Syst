<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'status' => fake()->boolean(90),
            'company_id' => Company::factory(),
            'unitName' => fake()->randomElement(['Piece', 'Box', 'Bottle', 'Strip', 'Pack']),
        ];
    }
}
