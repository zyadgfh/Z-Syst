<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\OnboardingTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantOnboardingInstanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'onboarding_template_id' => OnboardingTemplate::factory(),
            'initiated_by' => null,
            'status' => 'in_progress',
            'current_step' => 1,
            'total_steps' => 3,
            'progress_data' => null,
            'started_at' => now(),
            'completed_at' => null,
            'notes' => null,
        ];
    }
}
