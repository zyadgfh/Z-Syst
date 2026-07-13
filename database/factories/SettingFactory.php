<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'group' => $this->faker->randomElement(['general', 'sales', 'inventory']),
            'key' => $this->faker->unique()->word(),
            'value' => $this->faker->word(),
            'type' => 'string',
            'is_public' => $this->faker->boolean(20),
        ];
    }
}
