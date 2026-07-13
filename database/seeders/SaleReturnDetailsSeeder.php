<?php

namespace Database\Seeders;

use App\Models\SaleReturnDetails;
use Illuminate\Database\Seeder;

class SaleReturnDetailsSeeder extends Seeder
{
    public function run(): void
    {
        SaleReturnDetails::factory()->count(20)->create();
    }
}
