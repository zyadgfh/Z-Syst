<?php

namespace Database\Seeders;

use App\Models\StockTransfer;
use Illuminate\Database\Seeder;

class StockTransferSeeder extends Seeder
{
    public function run(): void
    {
        StockTransfer::factory()->count(10)->create();
    }
}
