<?php

namespace Database\Seeders;

use App\Models\GoodsReceivedNote;
use Illuminate\Database\Seeder;

class GoodsReceivedNoteSeeder extends Seeder
{
    public function run(): void
    {
        GoodsReceivedNote::factory()->count(10)->create();
    }
}
