<?php

namespace Database\Seeders;

use App\Models\PurchaseOrderReturnItem;
use Illuminate\Database\Seeder;

class PurchaseOrderReturnItemSeeder extends Seeder
{
    public function run(): void
    {
        PurchaseOrderReturnItem::factory()->count(15)->create();
    }
}
