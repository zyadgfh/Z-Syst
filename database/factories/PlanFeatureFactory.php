<?php

namespace Database\Factories;

use App\Models\PlanFeature;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFeatureFactory extends Factory
{
    protected $model = PlanFeature::class;

    public function definition(): array
    {
        return [
            'subscription_plan_id' => SubscriptionPlan::factory(),
            'feature_key' => $this->faker->unique()->word(),
            'feature_value' => $this->faker->sentence(3),
        ];
    }
}
