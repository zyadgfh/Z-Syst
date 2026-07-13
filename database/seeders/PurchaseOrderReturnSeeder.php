<?php

namespace Database\Seeders;

use App\Models\PurchaseOrderReturn;
use Illuminate\Database\Seeder;

class PurchaseOrderReturnSeeder extends Seeder
{
    public function run(): void
    {
        PurchaseOrderReturn::factory()->count(8)->create();
    }
}
