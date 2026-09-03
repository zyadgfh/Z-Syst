<?php

namespace Database\Factories;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->words(3, true),
            'type' => fake()->randomElement(['email', 'sms', 'notification']),
            'subject' => fake()->sentence(),
            'content' => fake()->paragraph(),
            'template' => fake()->optional()->word(),
            'target_segment' => fake()->word(),
            'scheduled_at' => fake()->optional()->dateTimeThisMonth(),
            'sent_at' => null,
            'status' => 'draft',
            'metadata' => null,
        ];
    }
}
