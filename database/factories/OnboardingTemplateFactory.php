<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OnboardingTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'code' => strtoupper(fake()->bothify('??-####')),
            'description' => fake()->sentence(),
            'steps' => [
                ['name' => 'Setup', 'description' => 'Initial setup'],
                ['name' => 'Configure', 'description' => 'Configure settings'],
            ],
            'default_settings' => null,
            'default_roles' => null,
            'default_permissions' => null,
            'is_active' => true,
            'is_default' => false,
        ];
    }
}
