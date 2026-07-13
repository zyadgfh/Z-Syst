<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Drug;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;

class DrugFactory extends Factory
{
    protected $model = Drug::class;

    public function definition(): array
    {
        return array_filter([
            'company_id' => Schema::hasColumn('drugs', 'company_id') ? Company::factory() : null,
            'business_id' => Schema::hasColumn('drugs', 'business_id') ? Company::factory() : null,
            'uuid' => $this->faker->uuid(),
            'name' => $this->faker->unique()->words(3, true),
            'generic_name' => $this->faker->word(),
            'barcode' => $this->faker->unique()->ean13(),
            'manufacturer' => $this->faker->company(),
            'form' => $this->faker->randomElement(['tablet', 'capsule', 'syrup', 'ointment', 'cream']),
            'strength' => $this->faker->randomElement(['100mg', '200mg', '250mg', '500mg']),
            'notes' => $this->faker->sentence(),
        ]);
    }
}
