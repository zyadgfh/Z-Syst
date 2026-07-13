<?php

namespace Database\Seeders;

use App\Models\SaleDetails;
use Illuminate\Database\Seeder;

class SaleDetailsSeeder extends Seeder
{
    public function run(): void
    {
        SaleDetails::factory()->count(20)->create();
    }
}
