<?php

namespace Database\Seeders;

use App\Models\InsurancePlan;
use Illuminate\Database\Seeder;

class InsurancePlanSeeder extends Seeder
{
    public function run(): void
    {
        InsurancePlan::factory()->count(10)->create();
    }
}
