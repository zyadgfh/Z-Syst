<?php

namespace Database\Seeders;

use App\Models\PurchaseOrderItem;
use Illuminate\Database\Seeder;

class PurchaseOrderItemSeeder extends Seeder
{
    public function run(): void
    {
        PurchaseOrderItem::factory()->count(20)->create();
    }
}
