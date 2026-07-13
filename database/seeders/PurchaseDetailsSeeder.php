<?php

namespace Database\Seeders;

use App\Models\PurchaseDetails;
use Illuminate\Database\Seeder;

class PurchaseDetailsSeeder extends Seeder
{
    public function run(): void
    {
        PurchaseDetails::factory()->count(15)->create();
    }
}
