<?php

namespace Database\Seeders;

use App\Models\SaleReturn;
use Illuminate\Database\Seeder;

class SaleReturnSeeder extends Seeder
{
    public function run(): void
    {
        SaleReturn::factory()->count(10)->create();
    }
}
