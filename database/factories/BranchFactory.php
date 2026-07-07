<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'name' => fake()->city().' Branch',
            'company_id' => Company::factory(),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'is_active' => fake()->boolean(90),
        ];
    }
}
