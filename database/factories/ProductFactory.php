<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        $data = [
            'company_id' => Company::factory(),
            'product_category_id' => ProductCategory::factory(),
            'sku' => $this->faker->unique()->bothify('PRD-#####'),
            'name' => $name,
            'slug' => $this->faker->slug(),
            'description' => $this->faker->sentence(),
            'cost_price' => $this->faker->randomFloat(2, 5, 100),
            'retail_price' => $this->faker->randomFloat(2, 10, 200),
            'wholesale_price' => $this->faker->randomFloat(2, 8, 150),
            'track_inventory' => true,
            'is_active' => true,
            'created_by' => 1,
            'updated_by' => 1,
        ];

        if (Schema::hasColumn('products', 'productName')) {
            $data['productName'] = $name;
        }

        return $data;
    }
}
