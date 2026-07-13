<?php

namespace Database\Seeders;

use App\Models\InsuranceClaim;
use Illuminate\Database\Seeder;

class InsuranceClaimSeeder extends Seeder
{
    public function run(): void
    {
        InsuranceClaim::factory()->count(10)->create();
    }
}
