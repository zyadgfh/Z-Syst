<?php

namespace Database\Seeders;

use App\Models\GrnItem;
use Illuminate\Database\Seeder;

class GrnItemSeeder extends Seeder
{
    public function run(): void
    {
        GrnItem::factory()->count(10)->create();
    }
}
