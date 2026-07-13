<?php

namespace Database\Seeders;

use App\Models\MedicineType;
use Illuminate\Database\Seeder;

class MedicineTypeSeeder extends Seeder
{
    public function run(): void
    {
        MedicineType::factory()->count(10)->create();
    }
}
