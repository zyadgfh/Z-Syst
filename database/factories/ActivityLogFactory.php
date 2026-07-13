<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'action' => fake()->randomElement(['created', 'updated', 'deleted', 'viewed']),
            'subject_type' => User::class,
            'subject_id' => null,
            'description' => fake()->sentence(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'properties' => ['browser' => fake()->browser(), 'platform' => fake()->platform()],
            'performed_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
