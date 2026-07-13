<?php

namespace Database\Seeders;

use App\Models\PurchaseReturnDetail;
use Illuminate\Database\Seeder;

class PurchaseReturnDetailSeeder extends Seeder
{
    public function run(): void
    {
        PurchaseReturnDetail::factory()->count(20)->create();
    }
}
