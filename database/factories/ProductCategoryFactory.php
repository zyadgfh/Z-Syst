<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->word(),
            'slug' => $this->faker->slug(),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
