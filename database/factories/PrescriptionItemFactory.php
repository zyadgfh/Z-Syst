<?php

namespace Database\Factories;

use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrescriptionItemFactory extends Factory
{
    protected $model = PrescriptionItem::class;

    public function definition(): array
    {
        return [
            'prescription_id' => Prescription::factory(),
            'product_id' => Product::factory(),
            'dosage' => $this->faker->randomElement(['1 tablet', '2 tablets', '1 ml', '10 ml']),
            'frequency' => $this->faker->randomElement(['once daily', 'twice daily', 'three times daily']),
            'duration' => $this->faker->randomElement(['3 days', '5 days', '7 days']),
            'quantity' => $this->faker->randomFloat(2, 1, 20),
            'dispensed_quantity' => $this->faker->randomFloat(2, 0, 20),
            'instructions' => $this->faker->sentence(),
            'substitution_allowed' => $this->faker->boolean(80),
        ];
    }
}
