<?php

namespace Database\Seeders;

use App\Models\DueCollect;
use Illuminate\Database\Seeder;

class DueCollectSeeder extends Seeder
{
    public function run(): void
    {
        DueCollect::factory()->count(10)->create();
    }
}
