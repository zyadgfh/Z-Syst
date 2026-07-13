<?php

namespace Database\Factories;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\GenericNotification',
            'data' => ['message' => fake()->sentence()],
            'read_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'notifiable_type' => null,
            'notifiable_id' => null,
        ];
    }
}
