<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Business;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Manufacturer;
use App\Models\MedicineType;
use App\Models\BoxSize;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'productName' => fake()->unique()->word() . ' ' . fake()->randomElement(['Tablet', 'Capsule', 'Syrup', 'Injection', 'Cream']),
            'category_id' => Category::factory(),
            'unit_id' => Unit::factory(),
            'manufacturer_id' => Manufacturer::factory(),
            'purchase_without_tax' => fake()->randomFloat(2, 1, 100),
            'purchase_with_tax' => fake()->randomFloat(2, 1, 100),
            'profit_percent' => fake()->randomFloat(2, 5, 50),
            'sales_price' => fake()->randomFloat(2, 10, 200),
            'wholesale_price' => fake()->randomFloat(2, 8, 150),
            'alert_qty' => fake()->numberBetween(5, 50),
            'productCode' => fake()->unique()->bothify('MED-####'),
            'tax_type' => fake()->randomElement(['exclusive', 'inclusive']),
        ];
    }
}