<?php

namespace Database\Seeders;

use App\Models\StockTransferItem;
use Illuminate\Database\Seeder;

class StockTransferItemSeeder extends Seeder
{
    public function run(): void
    {
        StockTransferItem::factory()->count(10)->create();
    }
}
