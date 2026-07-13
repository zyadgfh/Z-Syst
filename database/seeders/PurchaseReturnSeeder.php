<?php

namespace Database\Seeders;

use App\Models\PurchaseReturn;
use Illuminate\Database\Seeder;

class PurchaseReturnSeeder extends Seeder
{
    public function run(): void
    {
        PurchaseReturn::factory()->count(10)->create();
    }
}
